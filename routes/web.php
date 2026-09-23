<?php

use App\Http\Controllers\Auth\KaryawanAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Halaman yang dibuka saat QR tanda tangan pada dokumen cetak dipindai.
Route::get('/ttd', function (Request $request) {
    $teks = (string) $request->query('text', '');

    $isi = $request->hasValidRelativeSignature()
        ? "TANDA TANGAN TERVERIFIKASI\nDokumen diterbitkan oleh sistem dan isinya tidak diubah.\n\n{$teks}"
        : "TANDA TANGAN BELUM TERVERIFIKASI\nQR ini tidak memiliki tanda tangan sistem yang valid (dokumen lama atau tautan telah diubah).\n\n{$teks}";

    return response($isi, 200)->header('Content-Type', 'text/plain; charset=utf-8');
})->name('ttd.view');

Route::get('/registrasi/token', [KaryawanAuthController::class, 'preregistrasi'])->name('registrasi.pretoken');
Route::post('/registrasi/token', [KaryawanAuthController::class, 'verifyToken'])->middleware('throttle:register')->name('registrasi.verify');

Route::get('/registrasi-karyawan', [KaryawanAuthController::class, 'registrasi'])->name('registrasi');
Route::post('/registrasi-karyawan', [KaryawanAuthController::class, 'storeRegistrasi'])->middleware('throttle:register')->name('registrasi.store');
