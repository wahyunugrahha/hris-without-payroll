<?php

use App\Http\Controllers\Auth\KaryawanAuthController;
use App\Http\Controllers\Karyawan\DashboardController;
use App\Http\Controllers\Karyawan\DinasLuarController;
use App\Http\Controllers\Karyawan\Izin\IzinAbsenController;
use App\Http\Controllers\Karyawan\Izin\IzinCutiController;
use App\Http\Controllers\Karyawan\Izin\IzinPulangCepatController;
use App\Http\Controllers\Karyawan\Izin\IzinRosterController;
use App\Http\Controllers\Karyawan\Izin\IzinSakitController;
use App\Http\Controllers\Karyawan\Izin\IzinTerlambatController;
use App\Http\Controllers\Karyawan\KaryawanController;
use App\Http\Controllers\Karyawan\KenaikanGajiController;
use App\Http\Controllers\Karyawan\KPIController;
use App\Http\Controllers\Karyawan\LemburController;
use App\Http\Controllers\Karyawan\PengajuanIzinController;
use App\Http\Controllers\Karyawan\PresensiController;
use Illuminate\Support\Facades\Route;

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

    // PENGAJUAN IZIN PER JENIS (URL & nama route lama dipertahankan karena dipakai view)
    Route::prefix('pengajuanizin')->group(function () {
        Route::controller(IzinAbsenController::class)->group(function () {
            Route::get('/createizinabsen', 'create')->middleware('permission:izin-create-karyawan,karyawan')->name('karyawan.izin.absen.create');
            Route::post('/storeizinabsen', 'store')->middleware('permission:izin-create-karyawan,karyawan')->name('karyawan.izin.absen.store');
            Route::get('/{kode_izin}/editizinabsen', 'edit')->middleware('permission:izin-edit-karyawan,karyawan')->name('pengajuanizin.editizinabsen');
            Route::put('/{kode_izin}/updateizinabsen', 'update')->middleware('permission:izin-edit-karyawan,karyawan')->name('pengajuanizin.updateizinabsen');
        });
        Route::controller(IzinSakitController::class)->group(function () {
            Route::get('/createizinsakit', 'create')->middleware('permission:izin-create-karyawan,karyawan')->name('karyawan.izin.sakit.create');
            Route::post('/storeizinsakit', 'store')->middleware('permission:izin-create-karyawan,karyawan')->name('karyawan.izin.sakit.store');
            Route::get('/{kode_izin}/editizinsakit', 'edit')->middleware('permission:izin-edit-karyawan,karyawan')->name('pengajuanizin.editizinsakit');
            Route::put('/{kode_izin}/updateizinsakit', 'update')->middleware('permission:izin-edit-karyawan,karyawan')->name('pengajuanizin.updateizinsakit');
        });
        Route::controller(IzinCutiController::class)->group(function () {
            Route::get('/createizincuti', 'create')->middleware('permission:izin-create-karyawan,karyawan')->name('karyawan.izin.cuti.create');
            Route::post('/storeizincuti', 'store')->middleware('permission:izin-create-karyawan,karyawan')->name('pengajuanizin.storeizincuti');
            Route::get('/{kode_izin}/editizincuti', 'edit')->middleware('permission:izin-edit-karyawan,karyawan')->name('pengajuanizin.editizincuti');
            Route::put('/{kode_izin}/updateizincuti', 'update')->middleware('permission:izin-edit-karyawan,karyawan')->name('pengajuanizin.updateizincuti');
        });
        Route::controller(IzinRosterController::class)->group(function () {
            Route::get('/createizinroster', 'create')->middleware('permission:izin-create-karyawan,karyawan')->name('karyawan.izin.roster.create');
            Route::post('/storeizinroster', 'store')->middleware('permission:izin-create-karyawan,karyawan')->name('pengajuanizin.storeizinroster');
            Route::get('/{kode_izin}/editizinroster', 'edit')->middleware('permission:izin-edit-karyawan,karyawan')->name('pengajuanizin.editizinroster');
            Route::put('/{kode_izin}/updateizinroster', 'update')->middleware('permission:izin-edit-karyawan,karyawan')->name('pengajuanizin.updateizinroster');
        });
        Route::controller(IzinTerlambatController::class)->group(function () {
            Route::get('/createizinterlambat', 'create')->middleware('permission:izin-create-karyawan,karyawan')->name('karyawan.izin.terlambat.create');
            Route::post('/storeizinterlambat', 'store')->middleware('permission:izin-create-karyawan,karyawan')->name('karyawan.izin.terlambat.store');
            Route::get('/{kode_izin}/editizinterlambat', 'edit')->middleware('permission:izin-edit-karyawan,karyawan')->name('pengajuanizin.editizinterlambat');
            Route::put('/{kode_izin}/updateizinterlambat', 'update')->middleware('permission:izin-edit-karyawan,karyawan')->name('pengajuanizin.updateizinterlambat');
        });
        Route::controller(IzinPulangCepatController::class)->group(function () {
            Route::get('/createizinpulangcepat', 'create')->middleware('permission:izin-create-karyawan,karyawan')->name('karyawan.izin.pulangcepat.create');
            Route::post('/storeizinpulangcepat', 'store')->middleware('permission:izin-create-karyawan,karyawan')->name('karyawan.izin.pulangcepat.store');
            Route::get('/{kode_izin}/editizinpulangcepat', 'edit')->middleware('permission:izin-edit-karyawan,karyawan')->name('pengajuanizin.editizinpulangcepat');
            Route::put('/{kode_izin}/updateizinpulangcepat', 'update')->middleware('permission:izin-edit-karyawan,karyawan')->name('pengajuanizin.updateizinpulangcepat');
        });
    });

    Route::delete('/pengajuanizin/{kode_izin}/delete', [PengajuanIzinController::class, 'destroy'])
        ->middleware('permission:izin-delete-karyawan,karyawan')
        ->name('karyawan.izin.destroy');

    // Link "Edit" di daftar izin: diteruskan ke form edit sesuai jenis.
    Route::get('/pengajuanizin/{kode_izin}/edit', [PengajuanIzinController::class, 'edit'])
        ->middleware('permission:izin-edit-karyawan,karyawan')
        ->name('pengajuanizin.edit');

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
