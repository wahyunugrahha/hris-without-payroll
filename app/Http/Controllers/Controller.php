<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

abstract class Controller
{
    /**
     * Setelah logout satu guard: hapus seluruh sesi hanya bila guard lain juga tidak login.
     * Admin & karyawan berbagi cookie sesi; menghapus sesi saat guard lain masih aktif membuat
     * tab lain ikut ter-logout dan setiap form di sana gagal "Sesi telah berakhir" (419).
     */
    protected function akhiriSesi(Request $request, string $guardLain): void
    {
        if (Auth::guard($guardLain)->check()) {
            $request->session()->migrate(true);

            return;
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

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
