<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessException;
use Throwable;

abstract class Controller
{
    /**
     * True jika user adalah admin cabang dan karyawan target berada di cabang lain.
     * Dipakai di setiap aksi admin yang menerima ID dari request (approve, batal, hapus, detail).
     */
    protected function outsideAdminCabang($karyawan): bool
    {
        $user = auth('user')->user();

        return $user
            && $user->hasRole('admin cabang')
            && ($karyawan?->kode_cabang === null || $karyawan->kode_cabang !== $user->kode_cabang);
    }

    /**
     * Pesan gagal untuk user. Detail error teknis hanya masuk log, tidak bocor ke layar.
     */
    protected function failMessage(string $prefix, Throwable $e): string
    {
        if ($e instanceof BusinessException) {
            return trim($prefix.' '.$e->getMessage());
        }

        report($e);

        return trim($prefix.' Terjadi kesalahan sistem, silakan coba lagi atau hubungi admin.');
    }
}
