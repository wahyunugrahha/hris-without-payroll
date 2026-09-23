<?php

namespace App\Services;

use App\Models\JamKerja;
use App\Models\KonfigurasiJkDeptDetail;
use App\Models\Setjamkerja;
use Illuminate\Support\Facades\DB;

/**
 * Sumber tunggal untuk menentukan jadwal kerja karyawan pada suatu hari.
 */
class JadwalKerjaService
{
    private const NAMA_HARI = [
        'Sun' => 'Minggu',
        'Mon' => 'Senin',
        'Tue' => 'Selasa',
        'Wed' => 'Rabu',
        'Thu' => 'Kamis',
        'Fri' => 'Jumat',
        'Sat' => 'Sabtu',
    ];

    /**
     * Nama hari Indonesia dari singkatan Inggris hasil date('D'), mis. 'Mon' => 'Senin'.
     */
    public function namaHari(string $hariInggris): string
    {
        return self::NAMA_HARI[$hariInggris] ?? 'Minggu';
    }

    /**
     * Jadwal kerja untuk nama hari (Indonesia). Prioritas: jadwal personal, lalu jadwal departemen per cabang.
     *
     * @return array{0: ?JamKerja, 1: bool, 2: string} [jam kerja, apakah libur, sumber: personal|dept|none]
     */
    public function untukHari(?string $nik, ?string $kodeDept, ?string $kodeCabang, string $hari): array
    {
        if (empty($nik)) {
            return [null, false, 'none'];
        }

        $hariNormal = strtolower(trim($hari));

        $personal = Setjamkerja::with('jamKerja')
            ->where('nik', $nik)
            ->where(DB::raw('LOWER(hari)'), $hariNormal)
            ->first();

        if ($personal) {
            return [$personal->jamKerja, $this->isLibur($personal->kode_jam_kerja), 'personal'];
        }

        if ($kodeDept && $kodeCabang) {
            $dept = KonfigurasiJkDeptDetail::with('jamKerja')
                ->where(DB::raw('LOWER(hari)'), $hariNormal)
                ->whereHas('konfigurasi', fn ($q) => $q->where('kode_dept', $kodeDept)->where('kode_cabang', $kodeCabang))
                ->first();

            if ($dept) {
                return [$dept->jamKerja, $this->isLibur($dept->kode_jam_kerja), 'dept'];
            }
        }

        return [null, false, 'none'];
    }

    private function isLibur(?string $kodeJamKerja): bool
    {
        return $kodeJamKerja === null || $kodeJamKerja === 'LIBUR';
    }
}
