<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Izin;
use App\Models\Presensi;
use App\Support\CutiDatesMeta;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Keputusan admin atas pengajuan izin dan dampaknya ke tabel presensi.
 *
 * - Izin/sakit/cuti/roster disetujui: presensi tanggal tsb diganti record berstatus jenis izin.
 * - Terlambat disetujui: jam_in dikoreksi (dikurangi toleransi) pada record hadir hari itu.
 * - Pulang cepat disetujui: jam_out diisi jam pulang jadwal.
 * - Ditolak / dikembalikan ke pending: dampak di atas dibatalkan.
 */
class IzinApprovalService
{
    public const PENDING = 0;

    public const DISETUJUI = 1;

    public const DITOLAK = 2;

    /** Status presensi yang dibuat dari pengajuan izin. */
    private const STATUS_PRESENSI_IZIN = ['i', 's', 'c', 'r', 't', 'p'];

    public function __construct(private JadwalKerjaService $jadwalKerja) {}

    /**
     * @param  list<string>  $tanggalDipilih  untuk cuti/roster: tanggal yang disetujui (kosong = semua yang diajukan)
     */
    public function setujui(Izin $izin, array $tanggalDipilih = []): void
    {
        DB::transaction(function () use ($izin, $tanggalDipilih) {
            $tanggal = $this->tanggalDisetujui($izin, $tanggalDipilih);

            $this->bersihkanPresensiSebelumDisetujui($izin, $tanggal);

            foreach ($tanggal as $tgl) {
                match ($izin->status) {
                    'p' => $this->terapkanPulangCepat($izin, $tgl),
                    't' => $this->terapkanTerlambat($izin, $tgl),
                    default => $this->buatPresensiIzin($izin, $tgl),
                };
            }

            if (Izin::isMultiDateStatus($izin->status) && count($tanggal) < count($izin->tanggalDiajukan())) {
                $this->catatPersetujuanSebagian($izin, $tanggal);
            }

            $this->ubahStatus($izin, self::DISETUJUI);
            $izin->catatan_ditolak = null;
            $izin->save();
        });
    }

    public function tolak(Izin $izin, ?string $catatan): void
    {
        DB::transaction(function () use ($izin, $catatan) {
            $this->batalkanDampakPresensi($izin);
            $this->ubahStatus($izin, self::DITOLAK);
            if ($catatan !== null) {
                $izin->catatan_ditolak = $catatan;
            }
            $izin->save();
        });
    }

    public function kembalikanKePending(Izin $izin): void
    {
        DB::transaction(function () use ($izin) {
            $this->batalkanDampakPresensi($izin);
            $this->ubahStatus($izin, self::PENDING);
            $izin->catatan_ditolak = null;
            $izin->save();
        });
    }

    /**
     * @return list<string>
     */
    private function tanggalDisetujui(Izin $izin, array $tanggalDipilih): array
    {
        if (! Izin::isMultiDateStatus($izin->status)) {
            return CutiDatesMeta::datesFromRange($izin->tgl_izin_dari, $izin->tgl_izin_sampai);
        }

        $diajukan = $izin->tanggalDiajukan();
        // Admin hanya boleh menyetujui tanggal yang memang diajukan.
        $tanggal = empty($tanggalDipilih) ? $diajukan : array_values(array_intersect($diajukan, $tanggalDipilih));

        if (empty($tanggal)) {
            throw new BusinessException('Harap pilih minimal satu tanggal untuk pengajuan ini.');
        }

        return $tanggal;
    }

    private function bersihkanPresensiSebelumDisetujui(Izin $izin, array $tanggal): void
    {
        $rentang = [$izin->tgl_izin_dari, $izin->tgl_izin_sampai];

        // Terlambat/pulang cepat memakai record hadir yang ada sebagai basis, jadi jangan dihapus.
        if (in_array($izin->status, ['p', 't'], true)) {
            Presensi::where('nik', $izin->nik)->whereBetween('tgl_presensi', $rentang)->whereIn('status', ['t', 'p'])->delete();

            return;
        }

        // Record izin lama di rentang ini (mis. dari persetujuan sebelumnya) dibuang...
        Presensi::where('nik', $izin->nik)->whereBetween('tgl_presensi', $rentang)->whereIn('status', self::STATUS_PRESENSI_IZIN)->delete();
        // ...tapi hadir/anulir hanya diganti pada tanggal yang disetujui. Cuti tanggal 1 & 3 tidak boleh menghapus hadir tanggal 2.
        Presensi::where('nik', $izin->nik)->whereIn('tgl_presensi', $tanggal)->delete();
    }

    private function buatPresensiIzin(Izin $izin, string $tgl): void
    {
        [$jamKerja] = $this->jadwalUntuk($izin, $tgl);

        Presensi::create([
            'nik' => $izin->nik,
            'tgl_presensi' => $tgl,
            'kode_jam_kerja' => $jamKerja?->kode_jam_kerja ?? 'JK01',
            'foto_in' => '-',
            'foto_out' => '-',
            'lokasi_in' => '-',
            'lokasi_out' => '-',
            'status' => $izin->status,
            'jam_in' => '00:00:00',
            'jam_out' => '00:00:00',
        ]);
    }

    private function terapkanPulangCepat(Izin $izin, string $tgl): void
    {
        $presensi = Presensi::where('nik', $izin->nik)->whereDate('tgl_presensi', $tgl)->orderByDesc('id')->first();

        if (! $presensi || empty($presensi->jam_in) || $presensi->jam_in == '00:00:00') {
            throw new BusinessException('Karyawan belum memiliki data absen masuk untuk diproses pulang cepat.');
        }

        if (! empty($presensi->jam_out) && $presensi->jam_out != '00:00:00') {
            throw new BusinessException('Karyawan sudah absen pulang pada tanggal tersebut.');
        }

        [$jamKerja] = $this->jadwalUntuk($izin, $tgl);

        $jamPulang = $jamKerja ? $jamKerja->jam_pulang : '17:00:00';
        if (! empty($jamPulang) && strlen($jamPulang) === 5) {
            $jamPulang .= ':00';
        }

        if ($jamKerja && empty($presensi->kode_jam_kerja)) {
            $presensi->kode_jam_kerja = $jamKerja->kode_jam_kerja;
        }

        $presensi->status = 'h';
        $presensi->jam_out = $jamPulang;
        $presensi->foto_out = empty($presensi->foto_out) ? '-' : $presensi->foto_out;
        $presensi->lokasi_out = empty($presensi->lokasi_out) ? '-' : $presensi->lokasi_out;
        $presensi->save();
    }

    private function terapkanTerlambat(Izin $izin, string $tgl): void
    {
        [$jamKerja] = $this->jadwalUntuk($izin, $tgl);

        $presensi = Presensi::where('nik', $izin->nik)->whereDate('tgl_presensi', $tgl)->orderByDesc('id')->first()
            ?? new Presensi([
                'nik' => $izin->nik,
                'tgl_presensi' => $tgl,
                'kode_jam_kerja' => $jamKerja?->kode_jam_kerja ?? 'JK01',
                'foto_in' => '-',
                'lokasi_in' => '-',
            ]);

        $jamAcuan = ! empty($presensi->jam_in) && $presensi->jam_in !== '00:00:00'
            ? $presensi->jam_in
            : ($jamKerja ? $jamKerja->jam_masuk : '00:00:00');

        $presensi->status = 'h';
        $presensi->jam_in = $this->jamMasukSetelahToleransi($jamAcuan);
        $presensi->save();
    }

    /**
     * Jam masuk dikoreksi mundur sebesar toleransi keterlambatan (setting, default 10 menit).
     */
    private function jamMasukSetelahToleransi(?string $jamAcuan): string
    {
        if (empty($jamAcuan) || $jamAcuan === '00:00:00') {
            return '00:00:00';
        }

        try {
            return Carbon::createFromFormat('H:i:s', $jamAcuan)
                ->subMinutes((int) get_setting('toleransi_keterlambatan', 10))
                ->format('H:i:s');
        } catch (Exception) {
            return $jamAcuan;
        }
    }

    private function catatPersetujuanSebagian(Izin $izin, array $tanggal): void
    {
        sort($tanggal);
        $jumlahDiajukan = count($izin->tanggalDiajukan());
        $keterangan = CutiDatesMeta::strip($izin->keterangan);

        if (! str_contains($keterangan, '[PARTIAL]')) {
            $catatan = '[PARTIAL] Disetujui: '.count($tanggal)." dari {$jumlahDiajukan} hari.";
            $keterangan = $keterangan === '' ? $catatan : $keterangan.' | '.$catatan;
        }

        $izin->keterangan = CutiDatesMeta::append($keterangan, $tanggal);
        $izin->tgl_izin_dari = $tanggal[0];
        $izin->tgl_izin_sampai = end($tanggal);
    }

    /**
     * Membatalkan perubahan presensi yang dibuat saat pengajuan ini disetujui.
     */
    private function batalkanDampakPresensi(Izin $izin): void
    {
        if (in_array($izin->status, ['p', 't'], true)) {
            // Absen asli karyawan hanya disentuh jika sebelumnya memang dikoreksi oleh persetujuan.
            if ((int) $izin->status_approved !== self::DISETUJUI) {
                return;
            }

            $reset = $izin->status === 'p'
                ? ['jam_out' => null, 'foto_out' => '-', 'lokasi_out' => '-']
                : ['jam_in' => null, 'foto_in' => '-', 'lokasi_in' => '-'];

            Presensi::where('nik', $izin->nik)
                ->whereDate('tgl_presensi', $izin->tgl_izin_dari)
                ->where('status', 'h')
                ->update($reset);

            return;
        }

        Presensi::where('nik', $izin->nik)
            ->whereBetween('tgl_presensi', [$izin->tgl_izin_dari, $izin->tgl_izin_sampai])
            ->whereIn('status', self::STATUS_PRESENSI_IZIN)
            ->delete();
    }

    private function ubahStatus(Izin $izin, int $status): void
    {
        if ($status === self::PENDING) {
            $izin->status_decided_at = null;
        } elseif ($status !== (int) $izin->status_approved) {
            $izin->status_decided_at = now();
        }

        $izin->status_approved = $status;
    }

    private function jadwalUntuk(Izin $izin, string $tgl): array
    {
        return $this->jadwalKerja->untukHari(
            $izin->nik,
            $izin->karyawan?->kode_dept,
            $izin->karyawan?->kode_cabang,
            $this->jadwalKerja->namaHari(date('D', strtotime($tgl)))
        );
    }
}
