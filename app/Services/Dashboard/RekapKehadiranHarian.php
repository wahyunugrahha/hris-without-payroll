<?php

namespace App\Services\Dashboard;

use App\Models\DinasLuar;
use App\Models\HariLibur;
use App\Models\Karyawan;
use App\Models\Presensi;
use App\Services\Dashboard\Concerns\FiltersByUserContext;
use App\Services\JadwalKerjaService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Rekap kehadiran per tanggal untuk dashboard (kartu hari ini, pembanding, tren).
 *
 * Dihitung dari karyawan aktif yang wajib presensi:
 *  - hadir/terlambat : presensi status 'h' dengan jam masuk,
 *  - izin/sakit/cuti/roster : presensi status i/s/c/r (ditulis saat izin disetujui),
 *  - dinas_luar      : pengajuan dinas luar yang disetujui,
 *  - tanpa catatan   : libur (jadwal libur, libur nasional, atau Minggu tanpa jadwal) tidak dihitung;
 *                      hari ini sebelum batas jam masuk = belum_absen; selain itu = alpha.
 * Presensi 'x' (dianulir) dianggap tidak ada catatan.
 */
class RekapKehadiranHarian
{
    use FiltersByUserContext;

    private const STATUS_IZIN = ['i' => 'izin', 's' => 'sakit', 'c' => 'cuti', 'r' => 'roster'];

    public function __construct(private JadwalKerjaService $jadwalKerja) {}

    /**
     * @param  list<string>  $daftarTanggal  tanggal Y-m-d
     * @return array<string, array{wajib: int, dijadwalkan: int, hadir: int, terlambat: int, izin: int, sakit: int, cuti: int, roster: int, dinas_luar: int, alpha: int, belum_absen: int, libur: int}>
     */
    public function untuk(array $daftarTanggal, array $userCtx, ?CarbonInterface $sekarang = null): array
    {
        $sekarang ??= now();
        $daftarTanggal = array_values(array_unique($daftarTanggal));
        $dari = min($daftarTanggal);
        $sampai = max($daftarTanggal);

        $queryKaryawan = Karyawan::query()
            ->where('status_aktif', Karyawan::STATUS_AKTIF)
            ->wajibPresensi()
            ->select('nik', 'kode_dept', 'kode_cabang');
        $this->applyCabangFilter($queryKaryawan, $userCtx);
        $karyawan = $queryKaryawan->get();
        $nik = $karyawan->pluck('nik');

        // Presensi terakhir per karyawan per tanggal.
        $presensi = Presensi::query()
            ->leftJoin('jam_kerja', 'presensi.kode_jam_kerja', '=', 'jam_kerja.kode_jam_kerja')
            ->whereIn('presensi.nik', $nik)
            ->whereBetween('presensi.tgl_presensi', [$dari, $sampai])
            ->orderByDesc('presensi.id')
            ->get(['presensi.id', 'presensi.nik', 'presensi.tgl_presensi', 'presensi.status', 'presensi.jam_in', 'jam_kerja.jam_masuk'])
            ->groupBy(fn ($p) => Carbon::parse($p->tgl_presensi)->toDateString())
            ->map(fn (Collection $baris) => $baris->unique('nik')->keyBy('nik'));

        $dinas = DinasLuar::query()
            ->where('status_acc', 'acc')
            ->whereIn('nik', $nik)
            ->whereDate('tgl_mulai', '<=', $sampai)
            ->whereDate('tgl_selesai', '>=', $dari)
            ->get(['nik', 'tgl_mulai', 'tgl_selesai'])
            ->groupBy('nik');

        // Libur nasional per kombinasi cabang+departemen (sekali query per kombinasi).
        $libur = $karyawan
            ->map(fn ($k) => [$k->kode_cabang, $k->kode_dept])
            ->unique(fn ($p) => $p[0].'|'.$p[1])
            ->mapWithKeys(fn ($p) => [$p[0].'|'.$p[1] => array_flip(HariLibur::tanggalBerlaku($p[0], $p[1], $dari, $sampai))]);

        $jadwalPerHari = [];
        $hasil = [];

        foreach ($daftarTanggal as $tanggal) {
            $namaHari = $this->jadwalKerja->namaHari(date('D', strtotime($tanggal)));
            $jadwal = $jadwalPerHari[$namaHari] ??= $this->jadwalKerja->untukHariBanyak($karyawan, $namaHari);
            $presensiHari = $presensi->get($tanggal, collect());
            $hariIni = $tanggal === $sekarang->toDateString();
            $masaDepan = $tanggal > $sekarang->toDateString();

            $r = array_fill_keys(['dijadwalkan', 'hadir', 'terlambat', 'izin', 'sakit', 'cuti', 'roster', 'dinas_luar', 'alpha', 'belum_absen', 'libur'], 0);
            $r['wajib'] = $karyawan->count();

            foreach ($karyawan as $k) {
                $p = $presensiHari->get($k->nik);

                if ($p && $p->status === 'h' && $p->jam_in && $p->jam_in !== '00:00:00') {
                    $r['dijadwalkan']++;
                    $r['hadir']++;
                    if ($p->jam_masuk && $p->jam_in > $p->jam_masuk) {
                        $r['terlambat']++;
                    }

                    continue;
                }

                if ($p && isset(self::STATUS_IZIN[$p->status])) {
                    $r['dijadwalkan']++;
                    $r[self::STATUS_IZIN[$p->status]]++;

                    continue;
                }

                if ($dinas->get($k->nik, collect())->contains(fn ($d) => Carbon::parse($d->tgl_mulai)->toDateString() <= $tanggal
                    && Carbon::parse($d->tgl_selesai)->toDateString() >= $tanggal)) {
                    $r['dijadwalkan']++;
                    $r['dinas_luar']++;

                    continue;
                }

                [$jamKerja, $liburShift] = $jadwal[$k->nik] ?? [null, false];
                $liburNasional = isset($libur[$k->kode_cabang.'|'.$k->kode_dept][$tanggal]);
                if ($liburNasional || $liburShift || ($namaHari === 'Minggu' && ! $jamKerja)) {
                    $r['libur']++;

                    continue;
                }

                if ($masaDepan) {
                    continue;
                }

                $r['dijadwalkan']++;
                $batasMasuk = $jamKerja?->akhir_jam_masuk;
                if ($hariIni && (! $batasMasuk || $sekarang->format('H:i:s') <= $batasMasuk)) {
                    $r['belum_absen']++;
                } else {
                    $r['alpha']++;
                }
            }

            $hasil[$tanggal] = $r;
        }

        return $hasil;
    }
}
