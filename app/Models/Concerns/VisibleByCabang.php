<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Untuk model milik karyawan (relasi `karyawan`): admin cabang hanya melihat data karyawan di cabangnya.
 */
trait VisibleByCabang
{
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        $cabang = $user?->scopedCabang();

        return $query->when($cabang !== null, fn (Builder $q) => $q->whereHas(
            'karyawan',
            fn (Builder $karyawan) => $karyawan->where('kode_cabang', $cabang)
        ));
    }
}
