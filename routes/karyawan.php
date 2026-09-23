<?php

use App\Http\Controllers\Karyawan\KenaikanGajiController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\KaryawanAuthController;
use App\Http\Controllers\Karyawan\DashboardController;
use App\Http\Controllers\Karyawan\PengajuanIzinController;

use App\Http\Controllers\Karyawan\DinasLuarController;
use App\Http\Controllers\Karyawan\KPIController;
use App\Http\Controllers\Karyawan\LemburController;
use App\Http\Controllers\Karyawan\PresensiController;
use App\Http\Controllers\Karyawan\KaryawanController;

// Karyawan Routes
Route::middleware(['auth:karyawan'])->group(function () {

    // DASHBOARD
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard-view-karyawan,karyawan')
        ->name('dashboard.karyawan');

    // PRESENSI
    Route::get('/presensi/create', [PresensiController::class, 'create'])
        ->middleware(['permission:presensi-create-karyawan,karyawan', 'wajib_presensi'])
        ->name('karyawan.presensi.create');
    Route::post('/presensi/store', [PresensiController::class, 'store'])
        ->middleware('wajib_presensi')
        ->name('karyawan.presensi.store');

    // PROFILE
    Route::get('/presensi/profile', [KaryawanController::class, 'profile'])
        ->middleware('permission:profile-view-karyawan,karyawan')
        ->name('karyawan.profile.show');
    Route::get('/presensi/editprofile', [KaryawanController::class, 'editprofile'])
        ->middleware('permission:profile-edit-karyawan,karyawan')
        ->name('karyawan.profile.edit');
    Route::post('/updateprofile', [KaryawanController::class, 'updateprofile'])->name('karyawan.profile.update');

    Route::get('/presensi/profile/darurat', [KaryawanController::class, 'profileDarurat'])
        ->middleware('permission:profile-edit-karyawan,karyawan')
        ->name('karyawan.profile.darurat');
    Route::post('/presensi/profile/updatedarurat', [KaryawanController::class, 'updateDarurat'])
        ->name('karyawan.profile.updatedarurat');
        
    Route::get('/presensi/profile/administrasi', [KaryawanController::class, 'profileAdministrasi'])
        ->middleware('permission:profile-view-karyawan,karyawan')
        ->name('karyawan.profile.administrasi');
    Route::post('/presensi/profile/updateadministrasi', [KaryawanController::class, 'updateAdministrasi'])
        ->name('karyawan.profile.updateadministrasi');
    Route::post('/presensi/profile/ajukan-bpjs', [KaryawanController::class, 'ajukanBpjs'])
        ->name('karyawan.profile.ajukanbpjs');

    // AUTH
    Route::post('/proseslogout', [KaryawanAuthController::class, 'logout'])->name('proseslogout');

    // IZIN / SAKIT / CUTI
    Route::get('/presensi/izin', [PresensiController::class, 'formizin'])
        ->middleware('permission:izin-view-karyawan,karyawan')
        ->name('karyawan.izin.index');
    Route::get('/pengajuanizin/index', [PengajuanIzinController::class, 'index'])
        ->middleware('permission:izin-view-karyawan,karyawan')
        ->name('pengajuanizin.index');

    Route::get('/pengajuanizin/detail/{kode_izin}', [PengajuanIzinController::class, 'detail'])
        ->middleware('permission:izin-view-karyawan,karyawan')
        ->name('pengajuanizin.detail');

    // HISTORI PRESENSI
    Route::get('/presensi/histori', [PresensiController::class, 'histori'])
        ->middleware('permission:presensi-history-view-karyawan,karyawan')
        ->name('karyawan.presensi.history');
    Route::post('/presensi/gethistori', [PresensiController::class, 'gethistori'])
        ->name('presensi.gethistori');

    // PENGAJUAN IZIN DETAIL
    Route::post('/presensi/cekpengajuanizin', [PengajuanIzinController::class, 'cekPengajuanIzin'])->name('pengajuanizin.cekpengajuanizin');
    Route::post('/pengajuanizin/getblacklistdates', [PengajuanIzinController::class, 'getBlacklistDates'])->name('pengajuanizin.getblacklistdates');

    // CREATE IZIN (BY TYPE)
    Route::get('/pengajuanizin/createizinabsen', [PengajuanIzinController::class, 'createizinabsen'])
        ->middleware('permission:izin-create-karyawan,karyawan')
        ->name('karyawan.izin.absen.create');
    Route::get('/pengajuanizin/createizinsakit', [PengajuanIzinController::class, 'createizinsakit'])
        ->middleware('permission:izin-create-karyawan,karyawan')
        ->name('karyawan.izin.sakit.create');
    Route::get('/pengajuanizin/createizincuti', [PengajuanIzinController::class, 'createizincuti'])
        ->middleware('permission:izin-create-karyawan,karyawan')
        ->name('karyawan.izin.cuti.create');
    Route::get('/pengajuanizin/createizinroster', [PengajuanIzinController::class, 'createizinroster'])
        ->middleware('permission:izin-create-karyawan,karyawan')
        ->name('karyawan.izin.roster.create');
    Route::get('/pengajuanizin/createizinterlambat', [PengajuanIzinController::class, 'createizinterlambat'])
        ->middleware('permission:izin-create-karyawan,karyawan')
        ->name('karyawan.izin.terlambat.create');
    Route::get('/pengajuanizin/createizinpulangcepat', [PengajuanIzinController::class, 'createizinpulangcepat'])
        ->middleware('permission:izin-create-karyawan,karyawan')
        ->name('karyawan.izin.pulangcepat.create');

    // STORE IZIN (BY TYPE)
    Route::post('/pengajuanizin/storeizinabsen', [PengajuanIzinController::class, 'storeizinabsen'])
        ->name('karyawan.izin.absen.store');
    Route::post('/pengajuanizin/storeizinsakit', [PengajuanIzinController::class, 'storeizinsakit'])
        ->name('karyawan.izin.sakit.store');
    Route::post('/pengajuanizin/storeizincuti', [PengajuanIzinController::class, 'storeizincuti'])->name('pengajuanizin.storeizincuti');
    Route::post('/pengajuanizin/storeizinroster', [PengajuanIzinController::class, 'storeizinroster'])->name('pengajuanizin.storeizinroster');
    Route::post('/pengajuanizin/storeizinterlambat', [PengajuanIzinController::class, 'storeizinterlambat'])
        ->name('karyawan.izin.terlambat.store');
    Route::post('/pengajuanizin/storeizinpulangcepat', [PengajuanIzinController::class, 'storeizinpulangcepat'])
        ->name('karyawan.izin.pulangcepat.store');

    // DELETE IZIN
    Route::delete('/pengajuanizin/{kode_izin}/delete', [PengajuanIzinController::class, 'destroy'])
        ->middleware('permission:izin-delete-karyawan,karyawan')
        ->name('karyawan.izin.destroy');

    Route::get('/pengajuanizin/{kode_izin}/edit', [PengajuanIzinController::class, 'edit'])
        ->middleware('permission:izin-edit-karyawan,karyawan')
        ->name('pengajuanizin.edit');

    Route::put('/pengajuanizin/{kode_izin}/updateizinabsen', [PengajuanIzinController::class, 'updateizinabsen'])->name('pengajuanizin.updateizinabsen');
    Route::put('/pengajuanizin/{kode_izin}/updateizinsakit', [PengajuanIzinController::class, 'updateizinsakit'])->name('pengajuanizin.updateizinsakit');
    Route::put('/pengajuanizin/{kode_izin}/updateizincuti', [PengajuanIzinController::class, 'updateizincuti'])->name('pengajuanizin.updateizincuti');
    Route::put('/pengajuanizin/{kode_izin}/updateizinroster', [PengajuanIzinController::class, 'updateizinroster'])->name('pengajuanizin.updateizinroster');
    Route::put('/pengajuanizin/{kode_izin}/updateizinterlambat', [PengajuanIzinController::class, 'updateizinterlambat'])->name('pengajuanizin.updateizinterlambat');
    Route::put('/pengajuanizin/{kode_izin}/updateizinpulangcepat', [PengajuanIzinController::class, 'updateizinpulangcepat'])->name('pengajuanizin.updateizinpulangcepat');

    // Lembur
    Route::prefix('lembur')
        ->controller(LemburController::class)
        ->group(function () {
            Route::get('/', 'index')
                ->middleware('permission:lembur-view-karyawan,karyawan')
                ->name('lembur.index');
            Route::get('/create', 'create')
                ->middleware('permission:lembur-create-karyawan,karyawan')
                ->name('lembur.create');
            Route::post('/store', 'store')->name('lembur.store');

            Route::get('/{id}/edit', 'edit')
                ->middleware('permission:lembur-create-karyawan,karyawan')
                ->name('lembur.edit');
            Route::put('/{id}/update', 'update')->name('lembur.update');
            Route::delete('/{id}/delete', 'destroy')->name('lembur.destroy');

            Route::get('/{id}/absen-masuk', 'absenMasuk')->name('lembur.absenMasuk');
            Route::post('/{id}/absen-masuk', 'storeAbsenMasuk')->name('lembur.storeAbsenMasuk');
            Route::post('/{id}/cancel-draft', 'cancelDraft')->name('lembur.cancelDraft');

            Route::get('/{id}/absen-keluar', 'absenKeluar')->name('lembur.absenKeluar');
            Route::post('/{id}/absen-keluar', 'storeAbsenKeluar')->name('lembur.storeAbsenKeluar');
        });

    // User KPI
    Route::get('/kpi/indexkpi', [KPIController::class, 'indexKPI'])
        ->middleware('permission:kpi-input-karyawan,karyawan')
        ->name('kpi.user.index');
    Route::get('/kpi/createkpi', [KPIController::class, 'createKPI'])
        ->middleware('permission:kpi-input-karyawan,karyawan')
        ->name('kpi.user.create');
    Route::post('/kpi/storekpi', [KPIController::class, 'storeKPI'])
        ->name('kpi.user.store');
    Route::get('/kpi/{kpi_daily_id}/editkpi', [KPIController::class, 'editKPI'])
        ->name('kpi.user.edit');
    Route::put('/kpi/{kpi_daily_id}/updatekpi', [KPIController::class, 'updateKPI'])
        ->name('kpi.user.update');

    // Atasan KPI
    Route::get('/kpi/atasankpi', [KPIController::class, 'atasanIndex'])
        ->middleware('permission:kpi-approve-karyawan,karyawan')
        ->name('kpi.atasan.index');
    Route::get('/kpi/{kpi_daily_id}/detailatasankpi', [KPIController::class, 'atasanDetailKPI'])
        ->middleware('permission:kpi-approve-karyawan,karyawan')
        ->name('kpi.atasan.detail');
    Route::post('/kpi/{kpi_daily_id}/approve', [KPIController::class, 'approveKPI'])
        ->middleware('permission:kpi-approve-karyawan,karyawan')
        ->name('kpi.atasan.approve');
    Route::post('/kpi/{kpi_daily_id}/reject', [KPIController::class, 'rejectKPI'])
        ->middleware('permission:kpi-approve-karyawan,karyawan')
        ->name('kpi.atasan.reject');

    // Dinas Luar
    Route::get('/dinasluars', [DinasLuarController::class, 'index'])
        ->middleware('permission:dinasluar-view-karyawan,karyawan')
        ->name('dinasluars.index');
    Route::get('/dinasluars/create', [DinasLuarController::class, 'create'])
        ->middleware('permission:dinasluar-create-karyawan,karyawan')
        ->name('dinasluars.create');
    Route::post('/dinasluars', [DinasLuarController::class, 'store'])->name('dinasluars.store');
    Route::get('/dinasluars/{id}/edit', [DinasLuarController::class, 'edit'])
        ->middleware('permission:dinasluar-create-karyawan,karyawan')
        ->name('dinasluars.edit');
    Route::put('/dinasluars/{id}/update', [DinasLuarController::class, 'update'])->name('dinasluars.update');
    Route::delete('/dinasluars/{id}/delete', [DinasLuarController::class, 'destroy'])->name('dinasluars.destroy');

    // Kenaikan Gaji
    Route::get('/kenaikan-gaji', [KenaikanGajiController::class, 'index'])
        ->middleware('permission:kenaikan_gaji-view-karyawan,karyawan')
        ->name('karyawan.kenaikan_gaji.index');
    Route::post('/kenaikan-gaji', [KenaikanGajiController::class, 'store'])
        ->middleware('permission:kenaikan_gaji-view-karyawan,karyawan')
        ->name('karyawan.kenaikan_gaji.store');

    // Sudah tidak digunakan???
    Route::get('/presensi/buatizin', [PresensiController::class, 'buatizin'])
        ->middleware('permission:izin-create-karyawan,karyawan')
        ->name('karyawan.izin.create');
    Route::post('/presensi/storeizin', [PresensiController::class, 'storeizin'])
        ->name('karyawan.izin.store');
});