<?php

namespace App\Services;

use App\Models\JamKerja;
use App\Models\KonfigurasiJkDeptDetail;
use App\Models\Setjamkerja;
use Illuminate\Support\Collection;
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

    /**
     * Versi batch untukHari() untuk banyak karyawan sekaligus (2 query), aturan prioritas sama.
     *
     * @param  Collection<int, object{nik: string, kode_dept: ?string, kode_cabang: ?string}>  $karyawan
     * @return array<string, array{0: ?JamKerja, 1: bool, 2: string}> nik => [jam kerja, apakah libur, sumber]
     */
    public function untukHariBanyak(Collection $karyawan, string $hari): array
    {
        $hariNormal = strtolower(trim($hari));

        $personal = Setjamkerja::with('jamKerja')
            ->whereIn('nik', $karyawan->pluck('nik'))
            ->where(DB::raw('LOWER(hari)'), $hariNormal)
            ->get()
            ->groupBy('nik')
            ->map->first();

        $kunci = fn ($dept, $cabang) => trim((string) $dept).'|'.trim((string) $cabang);
        $dept = KonfigurasiJkDeptDetail::with(['jamKerja', 'konfigurasi'])
            ->where(DB::raw('LOWER(hari)'), $hariNormal)
            ->whereHas('konfigurasi', fn ($q) => $q->whereIn('kode_cabang', $karyawan->pluck('kode_cabang')->filter()->unique()))
            ->get()
            ->groupBy(fn ($d) => $kunci($d->konfigurasi->kode_dept, $d->konfigurasi->kode_cabang))
            ->map->first();

        $hasil = [];
        foreach ($karyawan as $k) {
            if ($p = $personal->get($k->nik)) {
                $hasil[$k->nik] = [$p->jamKerja, $this->isLibur($p->kode_jam_kerja), 'personal'];
            } elseif ($k->kode_dept && $k->kode_cabang && ($d = $dept->get($kunci($k->kode_dept, $k->kode_cabang)))) {
                $hasil[$k->nik] = [$d->jamKerja, $this->isLibur($d->kode_jam_kerja), 'dept'];
            } else {
                $hasil[$k->nik] = [null, false, 'none'];
            }
        }

        return $hasil;
    }

    private function isLibur(?string $kodeJamKerja): bool
    {
        return $kodeJamKerja === null || $kodeJamKerja === 'LIBUR';
    }
}
