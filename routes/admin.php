<?php

use App\Http\Controllers\Admin\BpjsController;
use App\Http\Controllers\Admin\CabangController;
use App\Http\Controllers\Admin\CabangLokasiController;
use App\Http\Controllers\Admin\CutiController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepartemenController;
use App\Http\Controllers\Admin\DinasLuarController;
use App\Http\Controllers\Admin\HariLiburController;
use App\Http\Controllers\Admin\IzinApprovalController;
use App\Http\Controllers\Admin\JabatanController;
use App\Http\Controllers\Admin\KaryawanController;
use App\Http\Controllers\Admin\KenaikanGajiController;
use App\Http\Controllers\Admin\KonfigurasiController;
use App\Http\Controllers\Admin\KonfigurasiUmumController;
use App\Http\Controllers\Admin\Kpi\LaporanKpiController;
use App\Http\Controllers\Admin\Kpi\MasterKpiController;
use App\Http\Controllers\Admin\Kpi\VerifikasiKpiController;
use App\Http\Controllers\Admin\LaporanPresensiController;
use App\Http\Controllers\Admin\LemburController;
use App\Http\Controllers\Admin\PengumumanController;
use App\Http\Controllers\Admin\PresensiController;
use App\Http\Controllers\Admin\SuratPeringatanController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AdminAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:user', 'permission:dashboard-view-admin,user'])->group(function () {

    Route::get('/panel/dashboardadmin', [DashboardController::class, 'dashboardadmin'])->name('dashboard.admin');
    // Menampilkan token registrasi & data karyawan: wajib login admin (TV kantor login sekali dengan akun khusus).
    Route::get('/dashboard-tv', [DashboardController::class, 'dashboardtv'])->name('dashboardtv');
    Route::get('/overview', [DashboardController::class, 'dashboardoverview'])->name('overview');
    Route::get('/proseslogoutadmin', [AdminAuthController::class, 'logout'])->name('proseslogoutadmin');

    // Account settings
    Route::get('/panel/account', [UserController::class, 'editSelf'])->name('users.account');
    Route::put('/panel/account', [UserController::class, 'updateSelf'])->name('users.account.update');

    // Master Karyawan
    Route::prefix('karyawan')->group(function () {
        Route::get('/', [KaryawanController::class, 'index'])
            ->middleware('permission:karyawan-view-admin,user')
            ->name('karyawan.index');
        Route::post('/store', [KaryawanController::class, 'store'])
            ->middleware('permission:karyawan-create-admin,user')
            ->name('karyawan.store');
        Route::get('/{nik}/edit', [KaryawanController::class, 'edit'])
            ->middleware('permission:karyawan-edit-admin,user')
            ->name('karyawan.edit');
        Route::put('/{nik}/update', [KaryawanController::class, 'update'])
            ->middleware('permission:karyawan-edit-admin,user')
            ->name('karyawan.update');
        Route::delete('/{nik}/delete', [KaryawanController::class, 'destroy'])
            ->middleware('permission:karyawan-delete-admin,user')
            ->name('karyawan.destroy');
        Route::get('/{nik}/show', [KaryawanController::class, 'show'])
            ->middleware('permission:karyawan-view-admin,user')
            ->name('karyawan.show');
        Route::get('/export', [KaryawanController::class, 'export'])
            ->middleware('permission:karyawan-export-excel-admin,user')
            ->name('karyawan.export');
        Route::get('/template', [KaryawanController::class, 'template'])
            ->middleware('permission:karyawan-download-template-admin,user')
            ->name('karyawan.template');
        Route::post('/import', [KaryawanController::class, 'import'])
            ->middleware('permission:karyawan-import-excel-admin,user')
            ->name('karyawan.import');
        Route::get('/monitoring-turnover', [KaryawanController::class, 'monitoringTurnover'])
            ->middleware('permission:karyawan-view-admin,user')
            ->name('karyawan.monitoring.turnover');
        Route::put('/{nik}/update-history', [KaryawanController::class, 'updateHistory'])
            ->middleware('permission:karyawan-edit-admin,user')
            ->name('karyawan.updatehistory');
    });

    // Jabatan
    Route::prefix('jabatan')->group(function () {
        Route::get('/', [JabatanController::class, 'index'])
            ->middleware('permission:jabatan-view-admin,user')
            ->name('jabatan.index');
        Route::get('/{id}/relations', [JabatanController::class, 'checkRelations'])
            ->middleware('permission:jabatan-view-admin,user')
            ->name('jabatan.relations');
        Route::post('/store', [JabatanController::class, 'store'])
            ->middleware('permission:jabatan-create-admin,user')
            ->name('jabatan.store');
        Route::get('/{id}/edit', [JabatanController::class, 'edit'])
            ->middleware('permission:jabatan-edit-admin,user')
            ->name('jabatan.edit');
        Route::put('/{id}/update', [JabatanController::class, 'update'])
            ->middleware('permission:jabatan-edit-admin,user')
            ->name('jabatan.update');
        Route::delete('/{id}/delete', [JabatanController::class, 'delete'])
            ->middleware('permission:jabatan-delete-admin,user')
            ->name('jabatan.delete');
    });

    // Master Departemen
    Route::prefix('departemen')->group(function () {
        Route::get('/', [DepartemenController::class, 'index'])
            ->middleware('permission:departemen-view-admin,user')
            ->name('departemen.index');
        Route::get('/{kode_dept}/relations', [DepartemenController::class, 'checkRelations'])
            ->middleware('permission:departemen-view-admin,user')
            ->name('departemen.relations');
        Route::post('/store', [DepartemenController::class, 'store'])
            ->middleware('permission:departemen-create-admin,user')
            ->name('departemen.store');
        Route::get('/{kode_dept}/edit', [DepartemenController::class, 'edit'])
            ->middleware('permission:departemen-edit-admin,user')
            ->name('departemen.edit');
        Route::put('/{kode_dept}/update', [DepartemenController::class, 'update'])
            ->middleware('permission:departemen-edit-admin,user')
            ->name('departemen.update');
        Route::delete('/{kode_dept}/delete', [DepartemenController::class, 'destroy'])
            ->middleware('permission:departemen-delete-admin,user')
            ->name('departemen.destroy');
    });

    // Master Cabang
    Route::prefix('cabang')->group(function () {
        Route::get('/', [CabangController::class, 'index'])
            ->middleware('permission:cabang-view-admin,user')
            ->name('cabang.index');
        Route::get('/{kode_cabang}/relations', [CabangController::class, 'checkRelations'])
            ->middleware('permission:cabang-view-admin,user')
            ->name('cabang.relations');
        Route::post('/store', [CabangController::class, 'store'])
            ->middleware('permission:cabang-create-admin,user')
            ->name('cabang.store');
        Route::get('/{kode_cabang}/edit', [CabangController::class, 'edit'])
            ->middleware('permission:cabang-edit-admin,user')
            ->name('cabang.edit');
        Route::put('/{kode_cabang}/update', [CabangController::class, 'update'])
            ->middleware('permission:cabang-edit-admin,user')
            ->name('cabang.update');
        Route::delete('/{kode_cabang}/delete', [CabangController::class, 'destroy'])
            ->middleware('permission:cabang-delete-admin,user')
            ->name('cabang.destroy');

        // Lokasi Cabang
        Route::get('/{kode_cabang}/lokasi', [CabangLokasiController::class, 'index'])
            ->middleware('permission:cabang-view-admin,user')
            ->name('cabanglokasi.index');
        Route::post('/{kode_cabang}/lokasi', [CabangLokasiController::class, 'store'])
            ->middleware('permission:cabang-create-admin,user')
            ->name('cabanglokasi.store');
        Route::put('/{kode_cabang}/lokasi/{id}', [CabangLokasiController::class, 'update'])
            ->middleware('permission:cabang-edit-admin,user')
            ->name('cabanglokasi.update');
        Route::delete('/{kode_cabang}/lokasi/{id}', [CabangLokasiController::class, 'destroy'])
            ->middleware('permission:cabang-delete-admin,user')
            ->name('cabanglokasi.destroy');
    });

    // Master Cuti
    Route::prefix('cuti')->group(function () {
        Route::get('/', [CutiController::class, 'index'])
            ->middleware('permission:cuti-view-admin,user')
            ->name('cuti.index');
        Route::get('/{kode_cuti}/relations', [CutiController::class, 'checkRelations'])
            ->middleware('permission:cuti-view-admin,user')
            ->name('cuti.relations');
        Route::post('/store', [CutiController::class, 'store'])
            ->middleware('permission:cuti-create-admin,user')
            ->name('cuti.store');
        Route::get('/{kode_cuti}/edit', [CutiController::class, 'edit'])
            ->middleware('permission:cuti-edit-admin,user')
            ->name('cuti.edit');
        Route::put('/{kode_cuti}/update', [CutiController::class, 'update'])
            ->middleware('permission:cuti-edit-admin,user')
            ->name('cuti.update');
        Route::delete('/{kode_cuti}', [CutiController::class, 'destroy'])
            ->middleware('permission:cuti-delete-admin,user')
            ->name('cuti.destroy');
    });

    // Presensi
    Route::prefix('presensi')->group(function () {
        // Monitoring
        Route::get('/monitoring', [PresensiController::class, 'monitoring'])
            ->middleware('permission:presensi-monitoring-view-admin,user')
            ->name('presensi.monitoring');
        Route::match(['get', 'post'], '/getpresensi', [PresensiController::class, 'getpresensi'])->middleware('permission:presensi-monitoring-view-admin,user')->name('presensi.getpresensi');

        // Batal Presensi
        Route::post('/monitoring/{id}/batal', [PresensiController::class, 'batalpresensi'])
            ->middleware('permission:presensi-monitoring-view-admin,user')
            ->name('presensi.batal');

        // Peta
        Route::post('/tampilkanpeta', [PresensiController::class, 'tampilkanpeta'])->middleware('permission:presensi-monitoring-view-admin,user')->name('presensi.tampilkanpeta');

        // Laporan & Rekap
        Route::get('/laporan', [LaporanPresensiController::class, 'laporan'])
            ->middleware('permission:laporan-view-admin,user')
            ->name('presensi.laporan');
        Route::post('/cetaklaporan', [LaporanPresensiController::class, 'cetaklaporan'])->middleware('permission:laporan-view-admin,user')->name('presensi.cetaklaporan');
        Route::get('/rekap', [LaporanPresensiController::class, 'rekap'])
            ->middleware('permission:laporan-view-admin,user')
            ->name('presensi.rekap');
        Route::post('/cetakrekap', [LaporanPresensiController::class, 'cetakrekap'])->middleware('permission:laporan-view-admin,user')->name('presensi.cetakrekap');

        // Izin Sakit (Halaman List Utama)
        Route::get('/izinsakit', [IzinApprovalController::class, 'index'])
            ->middleware('permission:pengajuan-izin-view-admin,user')
            ->name('presensi.izinsakit');
        // Detail Izin Sakit
        Route::get('/detailijinsakit/{id}', [IzinApprovalController::class, 'show'])
            ->middleware('permission:pengajuan-izin-view-admin,user')
            ->name('presensi.detailijinsakit');
        // Approval via Form (POST)
        Route::post('/approveizinsakit', [IzinApprovalController::class, 'update'])
            ->middleware('permission:pengajuan-izin-approve-admin,user')
            ->name('presensi.approveizinsakit');
        Route::post('/{id}/batalkanizinsakit', [IzinApprovalController::class, 'cancel'])
            ->middleware('permission:pengajuan-izin-approve-admin,user')
            ->name('presensi.batalkanizinsakit');
    });

    // Dinas Luar
    Route::prefix('dinas-luar')->group(function () {
        Route::get('/', [DinasLuarController::class, 'index'])
            ->middleware('permission:dinas-luar-view-admin,user')
            ->name('admin.dinas-luar.index');
        Route::get('/approval', [DinasLuarController::class, 'approval'])
            ->middleware('permission:dinas-luar-approve-admin,user')
            ->name('dinasluars.approval');
        Route::post('/approveorreject', [DinasLuarController::class, 'approveOrReject'])
            ->middleware('permission:dinas-luar-approve-admin,user')
            ->name('dinasluars.approveorreject');
        Route::post('/{id}/cancel', [DinasLuarController::class, 'cancel'])
            ->middleware('permission:dinas-luar-approve-admin,user')
            ->name('dinasluars.cancel');

        // Cetak Dinas Luar
        Route::get('/{id}/cetak', [DinasLuarController::class, 'cetak'])
            ->middleware('permission:dinas-luar-view-admin,user')
            ->name('dinasluars.cetak');
    });

    // Lembur
    Route::prefix('panel/lembur')
        ->controller(LemburController::class)
        ->group(function () {
            // Main Pages
            Route::get('/', 'index')
                ->middleware('permission:lembur-view-admin,user')
                ->name('admin.lembur.index');
            Route::get('/rekap', 'rekap')
                ->middleware('permission:laporan-view-admin,user')
                ->name('admin.lembur.rekap');
            Route::post('/cetakrekap', 'cetakrekap')->middleware('permission:laporan-view-admin,user')->name('admin.lembur.cetakrekap');

            // Approval via Form (POST)
            Route::get('/approval', 'approval')
                ->middleware('permission:lembur-approve-admin,user')
                ->name('admin.lembur.approval');
            Route::post('/approve', 'approve')
                ->middleware('permission:lembur-approve-admin,user')
                ->name('admin.lembur.approve');
            Route::post('/reject', 'reject')
                ->middleware('permission:lembur-approve-admin,user')
                ->name('admin.lembur.reject');
            Route::post('/cancel', 'cancel')
                ->middleware('permission:lembur-approve-admin,user')
                ->name('admin.lembur.cancel');
            Route::post('/update-jam', 'updateJam')
                ->middleware('permission:lembur-approve-admin,user')
                ->name('admin.lembur.update-jam');
        });

    // Surat Peringatan
    Route::prefix('panel/surat-peringatan')->group(function () {
        Route::get('/', [SuratPeringatanController::class, 'index'])
            ->middleware('permission:surat-peringatan-view-admin,user')
            ->name('suratperingatan.index');
        Route::post('/store', [SuratPeringatanController::class, 'store'])
            ->middleware('permission:surat-peringatan-manage-admin,user')
            ->name('suratperingatan.store');
        Route::get('/check-active/{nik}', [SuratPeringatanController::class, 'checkActive'])
            ->middleware('permission:surat-peringatan-manage-admin,user')
            ->name('suratperingatan.check-active');
        Route::post('/{id}/pemutihan', [SuratPeringatanController::class, 'pemutihan'])
            ->middleware('permission:surat-peringatan-manage-admin,user')
            ->name('suratperingatan.pemutihan');
        Route::get('/{id}/cetak', [SuratPeringatanController::class, 'cetak'])
            ->middleware('permission:surat-peringatan-view-admin,user')
            ->name('suratperingatan.cetak');
    });

    // Kenaikan Gaji
    Route::prefix('panel/kenaikan-gaji')->group(function () {
        Route::get('/', [KenaikanGajiController::class, 'index'])
            ->middleware('permission:kenaikan-gaji-view-admin,user')
            ->name('admin.kenaikan_gaji.index');
        Route::post('/{id}/approve', [KenaikanGajiController::class, 'approve'])
            ->middleware('permission:kenaikan-gaji-view-admin,user')
            ->name('admin.kenaikan_gaji.approve');
        Route::post('/{id}/reject', [KenaikanGajiController::class, 'reject'])
            ->middleware('permission:kenaikan-gaji-view-admin,user')
            ->name('admin.kenaikan_gaji.reject');
    });

    // BPJS
    Route::prefix('panel/bpjs')->group(function () {
        Route::get('/', [BpjsController::class, 'index'])
            ->middleware('permission:bpjs-view-admin|bpjs-tk-view-admin,user')
            ->name('admin.bpjs.index');
        Route::get('/{id}', [BpjsController::class, 'show'])
            ->middleware('permission:bpjs-view-admin|bpjs-tk-view-admin,user')
            ->name('admin.bpjs.show');
        Route::put('/{id}/update', [BpjsController::class, 'update'])
            ->middleware('permission:bpjs-view-admin|bpjs-tk-view-admin,user')
            ->name('admin.bpjs.update');
    });

    // KPI Management
    Route::prefix('kpi')->group(function () {
        Route::controller(MasterKpiController::class)->group(function () {
            Route::prefix('masterkpi')->name('kpi.master.')->group(function () {
                Route::get('/', 'masterKPI')->middleware('permission:kpi-view-admin,user')->name('index');
                Route::post('/store', 'storeMasterKPI')->middleware('permission:kpi-create-admin,user')->name('store');
                Route::get('/{id}/edit', 'editMasterKPI')->middleware('permission:kpi-edit-admin,user')->name('edit');
                Route::put('/{id}/update', 'updateMasterKPI')->middleware('permission:kpi-edit-admin,user')->name('update');
                Route::delete('/{id}/delete', 'deleteMasterKPI')->middleware('permission:kpi-delete-admin,user')->name('delete');
            });

            Route::prefix('detailmasterkpi')->name('kpi.master.detail.')->group(function () {
                Route::get('/{kpi_master_id}', 'detailMasterKPI')->middleware('permission:kpi-view-admin,user')->name('index');
                Route::post('/{kpi_master_id}/store', 'storeDetailMasterKPI')->middleware('permission:kpi-create-admin,user')->name('store');
                Route::put('/update', 'updateDetailMasterKPI')->middleware('permission:kpi-edit-admin,user')->name('update');
                Route::delete('/{detail_id}/{jenis}/delete', 'deleteDetailMasterKPI')->middleware('permission:kpi-delete-admin,user')->name('delete');
            });
        });

        // Verifikasi HR: hak per record diperiksa KPIDailyPolicy (role & cabang).
        Route::controller(VerifikasiKpiController::class)->group(function () {
            Route::prefix('indikatorkpi')->name('kpi.indikator.')->group(function () {
                Route::get('/', 'indikatorKPI')->middleware('permission:kpi-view-admin,user')->name('index');
                Route::get('/{kpi_daily_id}', 'detailIndikatorKPI')->middleware('permission:kpi-view-admin,user')->name('detail.index');
                Route::put('/{kpi_daily_id}/update', 'updateDetailIndikatorKPI')->middleware('permission:kpi-edit-admin,user')->name('detail.update');
                Route::put('/{kpi_daily_id}/approve', 'approveKPI')->middleware('permission:kpi-view-admin,user')->name('approve');
                Route::put('/{kpi_daily_id}/reject', 'rejectKPI')->middleware('permission:kpi-view-admin,user')->name('reject');
                Route::put('/extra/{id}/update', 'updateExtra')->middleware('permission:kpi-edit-admin,user')->name('extra.update');
                Route::delete('/extra/{id}/destroy', 'destroyExtra')->middleware('permission:kpi-edit-admin,user')->name('extra.destroy');
            });

            Route::post('/rekap/karyawan/bulk-approve', 'bulkApproveHR')->middleware('permission:kpi-edit-admin,user')->name('kpi.rekap.karyawan.bulk_approve');
        });

        Route::controller(LaporanKpiController::class)->middleware('permission:laporan-view-admin,user')->group(function () {
            Route::get('/rekap/karyawan', 'rekapKPIKaryawan')->name('kpi.rekap.karyawan');
            Route::post('/rekap/cetakKPI/karyawan', 'cetakRekapKPIKaryawan')->name('kpi.rekap.karyawan.cetak');
            Route::get('/report', 'reportKPI')->name('kpi.report');
            Route::post('/report/cetakKPI/', 'cetakReportKPI')->name('kpi.report.cetak');
        });
    });

    // Konfigurasi Jam Kerja
    Route::prefix('konfigurasi')->group(function () {
        Route::controller(KonfigurasiController::class)->group(function () {
            Route::get('/getjamkerja', 'getjamkerja')->name('konfigurasi.getjamkerja');

            // Jam Kerja Master
            Route::get('/jamkerja', 'jamkerja')
                ->middleware('permission:jam-kerja-view-admin,user')
                ->name('konfigurasi.jamkerja');
            Route::get('/jamkerja/{kode_jam_kerja}/relations', 'checkRelationsJamKerja')
                ->middleware('permission:jam-kerja-view-admin,user')
                ->name('konfigurasi.jamkerja.relations');
            Route::post('/storejamkerja', 'storejamkerja')
                ->middleware('permission:jam-kerja-create-admin,user')
                ->name('konfigurasi.storejamkerja');
            Route::get('/jamkerja/{kode_jam_kerja}/edit', 'editjamkerja')
                ->middleware('permission:jam-kerja-edit-admin,user')
                ->name('konfigurasi.editjamkerja');
            Route::put('/jamkerja/{kode_jam_kerja}/update', 'updatejamkerja')
                ->middleware('permission:jam-kerja-edit-admin,user')
                ->name('konfigurasi.updatejamkerja');
            Route::delete('/jamkerja/{kode_jam_kerja}/delete', 'destroyjamkerja')
                ->middleware('permission:jam-kerja-delete-admin,user')
                ->name('konfigurasi.destroyjamkerja');

            // Jam Kerja Departemen
            Route::get('/jamkerjadept', 'jamkerjadept')
                ->middleware('permission:jam-kerja-dept-view-admin,user')
                ->name('konfigurasi.jamkerjadept');
            Route::get('/jamkerjadept/create', 'createjamkerjadept')
                ->middleware('permission:jam-kerja-dept-create-admin,user')
                ->name('konfigurasi.createjamkerjadept');
            Route::post('/jamkerjadept/store', 'storejamkerjadept')
                ->middleware('permission:jam-kerja-dept-create-admin,user')
                ->name('konfigurasi.storejamkerjadept');
            Route::post('/jamkerjadept/setallbycabang', 'setallbycabang')
                ->middleware('permission:jam-kerja-dept-edit-admin,user')
                ->name('konfigurasi.setallbycabang');
            Route::get('/jamkerjadept/{kode_jk_dept}/edit', 'editjamkerjadept')
                ->middleware('permission:jam-kerja-dept-edit-admin,user')
                ->name('konfigurasi.editjamkerjadept');
            Route::post('/jamkerjadept/{kode_jk_dept}/update', 'updatejamkerjadept')
                ->middleware('permission:jam-kerja-dept-edit-admin,user')
                ->name('konfigurasi.updatejamkerjadept');
            Route::get('/jamkerjadept/{kode_jk_dept}/show', 'showjamkerjadept')
                ->middleware('permission:jam-kerja-dept-view-admin,user')
                ->name('konfigurasi.showjamkerjadept');
            Route::get('/jamkerjadept/{kode_jk_dept}/relations', 'checkRelationsJamKerjaDept')
                ->middleware('permission:jam-kerja-dept-view-admin,user')
                ->name('konfigurasi.jamkerjadept.relations');
            Route::delete('/jamkerjadept/{kode_jk_dept}/delete', 'deletejamkerjadept')
                ->middleware('permission:jam-kerja-dept-delete-admin,user')
                ->name('konfigurasi.deletejamkerjadept');

            // Jam Kerja Personal
            Route::middleware('permission:karyawan-edit-admin,user')->group(function () {
                Route::get('/{nik}/setjamkerja', 'setjamkerja')->name('konfigurasi.setjamkerja');
                Route::post('/setstorejamkerja', 'setstorejamkerja')->name('konfigurasi.setstorejamkerja');
                Route::post('/updatesetjamkerja', 'updatesetjamkerja')->name('konfigurasi.updatesetjamkerja');
            });
        });

        // Konfigurasi Umum
        Route::controller(KonfigurasiUmumController::class)->group(function () {
            Route::get('/umum', 'index')
                ->middleware('permission:konfigurasi-umum-view-admin,user')
                ->name('konfigurasi.umum.index');
            Route::post('/umum/update', 'update')
                ->middleware('permission:konfigurasi-umum-edit-admin,user')
                ->name('konfigurasi.umum.update');
        });
    });

    // Pengumuman
    Route::prefix('pengumuman')
        ->controller(PengumumanController::class)
        ->group(function () {
            Route::get('/', 'index')
                ->middleware('permission:pengumuman-view-admin,user')
                ->name('pengumuman.index');
            Route::post('/store', 'store')
                ->middleware('permission:pengumuman-create-admin,user')
                ->name('pengumuman.store');
            Route::put('/{id}/update', 'update')
                ->middleware('permission:pengumuman-edit-admin,user')
                ->name('pengumuman.update');
            Route::delete('/{id}/delete', 'destroy')
                ->middleware('permission:pengumuman-delete-admin,user')
                ->name('pengumuman.destroy');
            Route::post('/{id}/toggle', 'toggleStatus')
                ->middleware('permission:pengumuman-edit-admin,user')
                ->name('pengumuman.toggle');
        });

    // Users Management
    Route::prefix('users')
        ->controller(UserController::class)
        ->group(function () {
            // USERS
            Route::get('/', 'usersIndex')
                ->middleware('permission:users-view-admin,user')
                ->name('users.index');
            Route::post('/store', 'store')
                ->middleware('permission:users-create-admin,user')
                ->name('users.store');
            Route::get('/{id}/edit', 'edit')
                ->middleware('permission:users-edit-admin,user')
                ->name('users.edit');
            Route::put('/{id}/update', 'update')
                ->middleware('permission:users-edit-admin,user')
                ->name('users.update');
            Route::delete('/{id}/delete', 'destroy')
                ->middleware('permission:users-delete-admin,user')
                ->name('users.destroy');

            // ROLES
            Route::get('/roles', 'rolesIndex')
                ->middleware('permission:roles-view-admin,user')
                ->name('users.roles');
            Route::post('/roles', 'rolesStore')
                ->middleware('permission:roles-create-admin,user')
                ->name('users.roles.store');
            Route::put('/roles/{role}', 'rolesUpdate')
                ->middleware('permission:roles-edit-admin,user')
                ->name('users.roles.update');
            Route::delete('/roles/{role}', 'rolesDestroy')
                ->middleware('permission:roles-delete-admin,user')
                ->name('users.roles.destroy');

            // PERMISSIONS
            Route::get('/permissions', 'permissionsIndex')
                ->middleware('permission:permissions-view-admin,user')
                ->name('users.permissions');
            Route::post('/permissions', 'permissionsStore')
                ->middleware('permission:permissions-create-admin,user')
                ->name('users.permissions.store');
            Route::put('/permissions/{permission}', 'permissionsUpdate')
                ->middleware('permission:permissions-edit-admin,user')
                ->name('users.permissions.update');
            Route::delete('/permissions/{permission}', 'permissionsDestroy')
                ->middleware('permission:permissions-delete-admin,user')
                ->name('users.permissions.destroy');
        });

    // Hari Libur
    Route::middleware(['auth', 'permission:hari-libur-view-admin,user'])->group(function () {
        Route::get('/harilibur', [HariLiburController::class, 'index'])->name('harilibur.index');
        Route::get('/harilibur/create', [HariLiburController::class, 'create'])->name('harilibur.create');
        Route::post('/harilibur', [HariLiburController::class, 'store'])->name('harilibur.store');
        Route::get('/harilibur/{id}/edit', [HariLiburController::class, 'edit'])->name('harilibur.edit');
        Route::put('/harilibur/{id}', [HariLiburController::class, 'update'])->name('harilibur.update');
        Route::delete('/harilibur/{id}', [HariLiburController::class, 'destroy'])->name('harilibur.destroy');
    });

});
