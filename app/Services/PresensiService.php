<?php

namespace App\Services;

use App\Models\Cabang;
use App\Models\CabangLokasi;
use App\Models\HariLibur;
use App\Models\Karyawan;
use App\Models\Lembur;
use App\Models\Presensi;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Aturan presensi harian karyawan (halaman absen & proses simpan memakai aturan yang sama).
 */
class PresensiService
{
    /** Sebelum jam ini, absen setelah shift lintas hari masih dihitung untuk tanggal kemarin. */
    public const BATAS_LINTAS_HARI = '08:00';

    private const RADIUS_BUMI_METER = 6371000;

    public function __construct(private JadwalKerjaService $jadwalKerja) {}

    /**
     * Tanggal presensi yang sedang berjalan. Jika kemarin karyawan masuk shift lintas hari (mis. malam),
     * absen sebelum jam pulang shift tsb (atau sebelum BATAS_LINTAS_HARI) masih milik tanggal kemarin.
     */
    public function tanggalPresensiAktif(string $nik): string
    {
        $hariIni = date('Y-m-d');
        $kemarin = date('Y-m-d', strtotime('-1 day'));

        $presensiKemarin = Presensi::with('jamKerja')
            ->where('tgl_presensi', $kemarin)
            ->where('nik', $nik)
            ->orderByDesc('id')
            ->first();

        if (! $presensiKemarin?->jamKerja || $presensiKemarin->jamKerja->lintashari != 1) {
            return $hariIni;
        }

        $jamPulang = $presensiKemarin->jamKerja->jam_pulang;
        if (! empty($jamPulang) && strtotime(date('Y-m-d H:i')) <= strtotime($kemarin.' '.$jamPulang) + 86400) {
            return $kemarin;
        }

        return date('H:i') < self::BATAS_LINTAS_HARI ? $kemarin : $hariIni;
    }

    /**
     * @return array{libur: bool, nasional: bool, shift: bool}
     *                                                         libur jika hari libur nasional/cabang, jadwal shift libur, atau Minggu tanpa jadwal
     */
    public function statusLibur(Karyawan $karyawan, string $tanggal): array
    {
        $nasional = HariLibur::isHariLibur($tanggal, $karyawan->kode_cabang, $karyawan->kode_dept);
        $namaHari = $this->jadwalKerja->namaHari(date('D', strtotime($tanggal)));
        [$jamKerja, $shift] = $this->jadwalKerja->untukHari($karyawan->nik, $karyawan->kode_dept, $karyawan->kode_cabang, $namaHari);

        return [
            'libur' => $nasional || $shift || ($namaHari === 'Minggu' && empty($jamKerja)),
            'nasional' => $nasional,
            'shift' => $shift,
        ];
    }

    /**
     * Titik lokasi absen cabang; jika belum diatur per lokasi, pakai koordinat utama cabang.
     *
     * @return Collection<int, array{lat: float, lon: float, radius: int, nama: string}>
     */
    public function lokasiKantor(?string $kodeCabang): Collection
    {
        $lokasi = CabangLokasi::where('kode_cabang', $kodeCabang)->where('aktif', true)->get()
            ->map(fn ($lok) => [
                'lat' => (float) $lok->latitude,
                'lon' => (float) $lok->longitude,
                'radius' => (int) $lok->radius,
                'nama' => $lok->nama_lokasi,
            ]);

        $cabang = $lokasi->isEmpty() ? Cabang::find($kodeCabang) : null;
        if ($cabang && ! empty($cabang->lokasi_kantor)) {
            $koordinat = explode(',', $cabang->lokasi_kantor);
            $lokasi = collect([[
                'lat' => (float) ($koordinat[0] ?? 0),
                'lon' => (float) ($koordinat[1] ?? 0),
                'radius' => (int) ($cabang->radius ?? 0),
                'nama' => 'Lokasi Utama',
            ]]);
        }

        return $lokasi;
    }

    /**
     * True jika posisi berada di dalam radius salah satu lokasi kantor.
     */
    public function dalamRadius(Collection $lokasiKantor, float $lat, float $lon): bool
    {
        return $lokasiKantor->contains(fn ($lok) => self::jarakMeter($lok['lat'], $lok['lon'], $lat, $lon) <= $lok['radius']);
    }

    /**
     * Jarak dua koordinat (haversine), dalam meter.
     */
    public static function jarakMeter(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return self::RADIUS_BUMI_METER * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Saat absen masuk, lembur kemarin/hari ini yang belum ditutup otomatis diselesaikan
     * (dipotong di jam masuk shift bila lembur melewati jam masuk).
     */
    public function tutupLemburTerbuka(string $nik, string $tglPresensi, string $jamPresensi, string $fotoPresensi, $jamKerjaShift): void
    {
        try {
            $lembur = Lembur::where('nik', $nik)
                ->whereNull('jam_selesai')
                ->whereIn('tanggal_lembur', [date('Y-m-d', strtotime('-1 day', strtotime($tglPresensi))), $tglPresensi])
                ->orderByDesc('tanggal_lembur')
                ->orderByDesc('id')
                ->first();

            if (! $lembur) {
                return;
            }

            $waktuMulai = Carbon::parse(Carbon::parse($lembur->tanggal_lembur)->format('Y-m-d').' '.$lembur->jam_mulai);
            $jamSelesai = date('H:i', strtotime($jamPresensi));
            $waktuSelesai = Carbon::parse($tglPresensi.' '.$jamSelesai);

            if ($jamKerjaShift) {
                $karyawan = Karyawan::where('nik', $nik)->first();

                if (! HariLibur::isHariLibur($tglPresensi, $karyawan->kode_cabang, $karyawan->kode_dept)) {
                    $waktuShiftMulai = Carbon::parse($tglPresensi.' '.$jamKerjaShift->jam_masuk);
                    if ($waktuMulai->lt($waktuShiftMulai) && $waktuSelesai->gt($waktuShiftMulai)) {
                        $waktuSelesai = $waktuShiftMulai;
                        $jamSelesai = $waktuSelesai->format('H:i');
                    }
                }
            }

            if ($waktuSelesai->lt($waktuMulai)) {
                $waktuSelesai->addDay();
            }

            $lembur->update([
                'jam_selesai' => $jamSelesai,
                'total_jam' => round($waktuMulai->floatDiffInHours($waktuSelesai), 2),
                'foto_keluar' => $fotoPresensi,
            ]);
        } catch (Exception $e) {
            Log::error('AutoCloseLembur Error: '.$e->getMessage());
        }
    }
}
