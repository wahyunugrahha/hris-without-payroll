<?php

namespace App\Services;

use App\Models\HariLibur;
use App\Models\Izin;
use App\Models\Karyawan;
use App\Models\MasterCuti;
use App\Models\Presensi;
use Illuminate\Support\Collection;

/**
 * Aturan pengajuan izin/cuti yang dipakai bersama sisi karyawan (pengajuan) dan admin (approval).
 */
class IzinService
{
    public function __construct(private JadwalKerjaService $jadwalKerja) {}

    /**
     * Tanggal yang sudah tercatat di presensi sebagai cuti (atau roster) dalam rentang tersebut.
     *
     * @return list<string>
     */
    public function approvedCutiDates(string $nik, $dateFrom, $dateTo, ?string $izinStatus = 'c'): array
    {
        return Presensi::where('nik', $nik)
            ->where('status', $izinStatus === 'r' ? 'r' : 'c')
            ->whereBetween('tgl_presensi', [$dateFrom, $dateTo])
            ->orderBy('tgl_presensi')
            ->pluck('tgl_presensi')
            ->filter()
            ->map(fn ($date) => date('Y-m-d', strtotime((string) $date)))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * True jika salah satu tanggal sudah dipakai pengajuan lain (pending/disetujui) milik karyawan tersebut.
     */
    public function hasDateConflict(string $nik, Collection $selectedDates, ?string $excludeKodeIzin = null): bool
    {
        if ($selectedDates->isEmpty()) {
            return false;
        }

        $existingIzins = Izin::query()
            ->where('nik', $nik)
            ->whereIn('status_approved', [0, 1])
            ->where('tgl_izin_dari', '<=', $selectedDates->last())
            ->where('tgl_izin_sampai', '>=', $selectedDates->first())
            ->when($excludeKodeIzin, fn ($q) => $q->where('kode_izin', '!=', $excludeKodeIzin))
            ->get();

        $selectedLookup = array_flip($selectedDates->all());

        foreach ($existingIzins as $existingIzin) {
            foreach ($existingIzin->tanggalDiajukan() as $existingDate) {
                if (isset($selectedLookup[$existingDate])) {
                    return true;
                }
            }
        }

        return false;
    }

    public function kodeCutiTahunan(): string
    {
        return MasterCuti::where('nama_cuti', 'like', '%Cuti Tahunan%')->value('kode_cuti') ?? 'CTH';
    }

    /**
     * Jumlah hari cuti jenis tertentu yang sudah terpakai pada tahun tersebut:
     * tanggal cuti yang disetujui & tercatat di presensi, tidak termasuk hari libur / libur shift.
     */
    public function cutiTakenDays(Karyawan $karyawan, $tahun, $kodeCuti, ?string $excludeKodeIzin = null): int
    {
        $nik = $karyawan->nik;
        $holidayLookup = array_flip(HariLibur::tanggalBerlaku($karyawan->kode_cabang, $karyawan->kode_dept, "$tahun-01-01", "$tahun-12-31"));

        $rows = Izin::query()
            ->select('nik', 'status', 'keterangan', 'tgl_izin_dari', 'tgl_izin_sampai')
            ->where('nik', $nik)
            ->where('status', 'c')
            ->where('kode_cuti', $kodeCuti)
            ->where('status_approved', 1)
            ->whereYear('tgl_izin_dari', $tahun)
            ->when($excludeKodeIzin, fn ($q) => $q->where('kode_izin', '!=', $excludeKodeIzin))
            ->get();

        $approvedLookup = array_flip(Presensi::where('nik', $nik)
            ->where('status', 'c')
            ->whereYear('tgl_presensi', $tahun)
            ->pluck('tgl_presensi')
            ->filter()
            ->map(fn ($date) => $date->format('Y-m-d'))
            ->all());

        $sumDays = 0;
        foreach ($rows as $izin) {
            foreach ($izin->tanggalDiajukan() as $dateStr) {
                if ((int) date('Y', strtotime($dateStr)) !== (int) $tahun
                    || isset($holidayLookup[$dateStr])
                    || ! isset($approvedLookup[$dateStr])) {
                    continue;
                }

                $namaHari = $this->jadwalKerja->namaHari(date('D', strtotime($dateStr)));
                [, $isLiburShift] = $this->jadwalKerja->untukHari($nik, $karyawan->kode_dept, $karyawan->kode_cabang, $namaHari);

                if (! $isLiburShift) {
                    $sumDays++;
                }
            }
        }

        return $sumDays;
    }

    /**
     * Kode izin baru untuk pengajuan yang dimulai pada tanggal tersebut, mis. IZ09260013.
     */
    public function nextKodeIzin(string $tanggal): string
    {
        $bulan = date('m', strtotime($tanggal));
        $tahun = date('Y', strtotime($tanggal));

        $lastKode = Izin::whereMonth('tgl_izin_dari', $bulan)
            ->whereYear('tgl_izin_dari', $tahun)
            ->orderByDesc('kode_izin')
            ->value('kode_izin');

        return $this->generateKodeIzin($lastKode, 'IZ'.$bulan.substr($tahun, 2, 2), 4);
    }

    /**
     * True jika salah satu tanggal sudah punya presensi (hadir/izin/sakit/cuti/roster) atau pengajuan lain.
     */
    public function tanggalBentrok(string $nik, Collection $dates, ?string $excludeKodeIzin = null): bool
    {
        return Presensi::where('nik', $nik)
            ->whereIn('tgl_presensi', $dates->all())
            ->whereIn('status', ['h', 'i', 's', 'c', 'r'])
            ->exists()
            || $this->hasDateConflict($nik, $dates, $excludeKodeIzin);
    }

    /**
     * Tanggal pilihan dikurangi hari libur & libur shift karyawan tersebut.
     */
    public function tanggalHariKerja(Karyawan $karyawan, Collection $dates): Collection
    {
        if ($dates->isEmpty()) {
            return $dates;
        }

        $libur = HariLibur::tanggalBerlaku($karyawan->kode_cabang, $karyawan->kode_dept, $dates->first(), $dates->last());

        return $dates->reject(function ($tanggal) use ($karyawan, $libur) {
            if (in_array($tanggal, $libur, true)) {
                return true;
            }

            $namaHari = $this->jadwalKerja->namaHari(date('D', strtotime($tanggal)));
            [, $isLiburShift] = $this->jadwalKerja->untukHari($karyawan->nik, $karyawan->kode_dept, $karyawan->kode_cabang, $namaHari);

            return $isLiburShift;
        })->values();
    }

    /**
     * Sisa jatah cuti tahun ini untuk satu jenis cuti; null berarti tanpa batas.
     */
    public function sisaCuti(Karyawan $karyawan, MasterCuti $masterCuti, ?string $excludeKodeIzin = null): ?int
    {
        if ((int) $masterCuti->jml_hari <= 0) {
            return null;
        }

        return max(0, (int) $masterCuti->jml_hari - $this->cutiTakenDays($karyawan, date('Y'), $masterCuti->kode_cuti, $excludeKodeIzin));
    }

    /**
     * @return array<string, ?int> [kode_cuti => sisa jatah | null]
     */
    public function sisaCutiMap(Karyawan $karyawan, Collection $masterCuti, ?string $excludeKodeIzin = null): array
    {
        return $masterCuti
            ->mapWithKeys(fn (MasterCuti $mc) => [$mc->kode_cuti => $this->sisaCuti($karyawan, $mc, $excludeKodeIzin)])
            ->all();
    }

    /**
     * Kode izin berikutnya dengan format prefix + nomor urut, mis. IZ0926 + 0001.
     *
     * ponytail: nomor diambil dari kode terakhir tanpa lock; dua pengajuan bersamaan bisa bentrok
     * di primary key (pengajuan kedua gagal). Pakai sequence/lock jika volume tinggi.
     */
    public function generateKodeIzin(?string $lastKode, string $format, int $padLength): string
    {
        $nextNumber = (empty($lastKode) || ! str_starts_with($lastKode, $format))
            ? 1
            : (int) substr($lastKode, -4) + 1;

        return $format.str_pad((string) $nextNumber, $padLength, '0', STR_PAD_LEFT);
    }
}
