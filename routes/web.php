<?php

use App\Http\Controllers\Auth\KaryawanAuthController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard-tv', [DashboardController::class, 'dashboardtv'])->name('dashboardtv');
Route::get('/overview', [DashboardController::class, 'dashboardoverview'])->name('overview');

Route::get('/ttd', function (Request $request) {
	return response($request->get('text', ''), 200)
		->header('Content-Type', 'text/plain; charset=utf-8');
})->name('ttd.view');

Route::get('/registrasi/token', [KaryawanAuthController::class, 'preregistrasi'])->name('registrasi.pretoken');
Route::post('/registrasi/token', [KaryawanAuthController::class, 'verifyToken'])->middleware('throttle:register')->name('registrasi.verify');

Route::get('/registrasi-karyawan', [KaryawanAuthController::class, 'registrasi'])->name('registrasi');
Route::post('/registrasi-karyawan', [KaryawanAuthController::class, 'storeRegistrasi'])->middleware('throttle:register')->name('registrasi.store');