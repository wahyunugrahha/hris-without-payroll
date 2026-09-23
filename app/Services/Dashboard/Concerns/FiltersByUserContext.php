<?php

namespace App\Services\Dashboard\Concerns;

/**
 * Filter cabang (admin cabang / filter dropdown) & kata kunci untuk query widget dashboard admin.
 */
trait FiltersByUserContext
{
    /**
     * Helper: Filter Query Cabang untuk Admin
     */
    protected function applyCabangFilter($query, $userCtx, $tablePrefix = null)
    {
        $cabangColumn = $tablePrefix ? $tablePrefix.'.kode_cabang' : 'kode_cabang';
        $deptColumn = $tablePrefix ? $tablePrefix.'.kode_dept' : 'kode_dept';

        if (! empty($userCtx['filterCabang'])) {
            $query->where($cabangColumn, $userCtx['filterCabang']);
        } elseif ($userCtx['isAdminCabang'] && $userCtx['kodeCabang']) {
            $query->where($cabangColumn, $userCtx['kodeCabang']);
        }

        if (! empty($userCtx['filterDept'])) {
            $query->where($deptColumn, $userCtx['filterDept']);
        }

        return $query;
    }

    protected function applyKeywordFilter($query, $userCtx, $tablePrefix = null)
    {
        $keyword = strtolower(trim((string) ($userCtx['filterQ'] ?? '')));
        if ($keyword === '') {
            return $query;
        }

        $nikColumn = $tablePrefix ? $tablePrefix.'.nik' : 'nik';
        $namaColumn = $tablePrefix ? $tablePrefix.'.nama_lengkap' : 'nama_lengkap';

        $query->where(function ($sub) use ($nikColumn, $namaColumn, $keyword) {
            $sub->whereRaw("LOWER({$nikColumn}) LIKE ?", ["%{$keyword}%"])
                ->orWhereRaw("LOWER({$namaColumn}) LIKE ?", ["%{$keyword}%"]);
        });

        return $query;
    }
}
