<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessException;
use Throwable;

abstract class Controller
{
    /**
     * Cabang yang membatasi admin yang login, atau null jika boleh melihat semua cabang.
     */
    protected function scopedCabang(): ?string
    {
        return auth('user')->user()?->scopedCabang();
    }

    /**
     * True jika admin yang login dibatasi cabang dan karyawan target berada di cabang lain.
     * Dipakai di aksi admin yang menerima ID dari request (approve, batal, hapus, detail).
     */
    protected function outsideAdminCabang($karyawan): bool
    {
        $cabang = $this->scopedCabang();

        return $cabang !== null && $karyawan?->kode_cabang !== $cabang;
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
