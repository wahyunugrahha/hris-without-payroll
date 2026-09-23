<?php

namespace App\Policies;

use App\Models\KPIDaily;
use App\Models\User;

/**
 * Siapa yang boleh memverifikasi / menyetujui / menolak KPI harian karyawan dari panel admin.
 */
class KPIDailyPolicy
{
    private const ROLE_VERIFIKATOR = ['administrator', 'hrd', 'admin cabang'];

    public function verifikasi(User $user, KPIDaily $kpiDaily): bool
    {
        if (! $user->hasRole(self::ROLE_VERIFIKATOR)) {
            return false;
        }

        $cabang = $user->scopedCabang();

        return $cabang === null || $kpiDaily->karyawan?->kode_cabang === $cabang;
    }

    /**
     * Ubah isi penilaian: seperti verifikasi, tapi tidak lagi setelah disetujui HR.
     */
    public function ubah(User $user, KPIDaily $kpiDaily): bool
    {
        return $kpiDaily->status !== 'approved_by_hr' && $this->verifikasi($user, $kpiDaily);
    }

    /**
     * Persetujuan massal (tanpa record tertentu); pembatasan cabang dilakukan pada daftar NIK.
     */
    public function verifikasiMassal(User $user): bool
    {
        return $user->hasRole(self::ROLE_VERIFIKATOR);
    }
}
