<?php

use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\KaryawanAuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Login Karyawan
Route::middleware('guest:karyawan')->group(function () {

    Route::get('/', function () {
        // Jika sudah login sebagai admin, arahkan ke dashboard admin
        if (Auth::guard('user')->check()) {
            return redirect()->route('dashboard.admin');
        }

        // Jika sudah login sebagai karyawan, arahkan ke dashboard karyawan (fallback)
        if (Auth::guard('karyawan')->check()) {
            return redirect()->route('dashboard.karyawan');
        }

        return view('auth.login');
    })->name('login');

    Route::post('/login', [KaryawanAuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('login.process');
});

// Login Admin
Route::middleware('guest:user')->group(function () {

    Route::get('/panel', function () {
        // Jika sudah login sebagai admin, langsung ke dashboard admin
        if (Auth::guard('user')->check()) {
            return redirect()->route('dashboard.admin');
        }

        // Jika sudah login sebagai karyawan, arahkan ke dashboard karyawan
        if (Auth::guard('karyawan')->check()) {
            return redirect()->route('dashboard.karyawan');
        }

        return view('auth.loginadmin');
    })->name('loginadmin');

    Route::post('/panel/login', [AdminAuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('loginadmin.process');
});
