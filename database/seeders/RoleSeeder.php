<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache roles dan permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | GUARD: USER (Panel Admin)
        |--------------------------------------------------------------------------
        */

        $adminPermissions = [
            'dashboard-view-admin',

            // Master Data
            'karyawan-view-admin', 'karyawan-create-admin', 'karyawan-edit-admin', 'karyawan-delete-admin', 'karyawan-export-excel-admin',
            'jabatan-view-admin', 'jabatan-create-admin', 'jabatan-edit-admin', 'jabatan-delete-admin',
            'departemen-view-admin', 'departemen-create-admin', 'departemen-edit-admin', 'departemen-delete-admin',
            'cabang-view-admin', 'cabang-create-admin', 'cabang-edit-admin', 'cabang-delete-admin',
            'cuti-view-admin', 'cuti-create-admin', 'cuti-edit-admin', 'cuti-delete-admin',

            // Operasional
            'presensi-monitoring-view-admin',
            'pengajuan-izin-view-admin', 'pengajuan-izin-approve-admin',
            'lembur-view-admin', 'lembur-approve-admin',
            'dinas-luar-view-admin', 'dinas-luar-approve-admin',
            'kpi-view-admin', 'kpi-create-admin', 'kpi-edit-admin', 'kpi-delete-admin',
            'kenaikan-gaji-view-admin', 'bpjs-view-admin',
            'surat-peringatan-view-admin', 'surat-peringatan-manage-admin',
            'laporan-view-admin',

            // Settings
            'jam-kerja-view-admin', 'jam-kerja-create-admin', 'jam-kerja-edit-admin', 'jam-kerja-delete-admin',
            'jam-kerja-dept-view-admin', 'jam-kerja-dept-create-admin', 'jam-kerja-dept-edit-admin', 'jam-kerja-dept-delete-admin',
            'hari-libur-view-admin', 'hari-libur-create-admin', 'hari-libur-edit-admin', 'hari-libur-delete-admin',
            'pengumuman-view-admin', 'pengumuman-create-admin', 'pengumuman-edit-admin', 'pengumuman-delete-admin',
            'konfigurasi-umum-view-admin', 'konfigurasi-umum-edit-admin',

            // User Management
            'users-view-admin', 'users-create-admin', 'users-edit-admin', 'users-delete-admin',
            'roles-view-admin', 'roles-create-admin', 'roles-edit-admin', 'roles-delete-admin',
            'permissions-view-admin', 'permissions-create-admin', 'permissions-edit-admin', 'permissions-delete-admin',
        ];

        $hrdPermissions = [
            'dashboard-view-admin',

            // Master Data
            'karyawan-view-admin', 'karyawan-create-admin', 'karyawan-edit-admin', 'karyawan-delete-admin', 'karyawan-export-excel-admin',
            'jabatan-view-admin', 'jabatan-create-admin', 'jabatan-edit-admin', 'jabatan-delete-admin',
            'departemen-view-admin', 'departemen-create-admin', 'departemen-edit-admin', 'departemen-delete-admin',
            'cabang-view-admin', 'cabang-create-admin', 'cabang-edit-admin', 'cabang-delete-admin',
            'cuti-view-admin', 'cuti-create-admin', 'cuti-edit-admin', 'cuti-delete-admin',

            // Operasional
            'presensi-monitoring-view-admin',
            'pengajuan-izin-view-admin', 'pengajuan-izin-approve-admin',
            'lembur-view-admin', 'lembur-approve-admin',
            'dinas-luar-view-admin', 'dinas-luar-approve-admin',
            'kpi-view-admin', 'kpi-create-admin', 'kpi-edit-admin', 'kpi-delete-admin',
            'kenaikan-gaji-view-admin', 'bpjs-view-admin',
            'surat-peringatan-view-admin', 'surat-peringatan-manage-admin',
            'laporan-view-admin',

            // Settings
            'jam-kerja-view-admin', 'jam-kerja-create-admin', 'jam-kerja-edit-admin', 'jam-kerja-delete-admin',
            'jam-kerja-dept-view-admin', 'jam-kerja-dept-create-admin', 'jam-kerja-dept-edit-admin', 'jam-kerja-dept-delete-admin',
            'hari-libur-view-admin', 'hari-libur-create-admin', 'hari-libur-edit-admin', 'hari-libur-delete-admin',
            'pengumuman-view-admin', 'pengumuman-create-admin', 'pengumuman-edit-admin', 'pengumuman-delete-admin',

            // User Management
            'users-view-admin', 'users-create-admin', 'users-edit-admin', 'users-delete-admin',
            'roles-view-admin', 'roles-create-admin', 'roles-edit-admin', 'roles-delete-admin',
            'permissions-view-admin',
        ];

        $ownerPermissions = [
            'dashboard-view-admin', 'karyawan-view-admin', 'jabatan-view-admin', 'departemen-view-admin',
            'cabang-view-admin', 'cuti-view-admin', 'presensi-monitoring-view-admin', 'pengajuan-izin-view-admin',
            'lembur-view-admin', 'dinas-luar-view-admin', 'kpi-view-admin', 'surat-peringatan-view-admin',
            'kenaikan-gaji-view-admin', 'bpjs-view-admin',
            'laporan-view-admin', 'jam-kerja-view-admin', 'jam-kerja-dept-view-admin', 'hari-libur-view-admin',
            'pengumuman-view-admin', 'users-view-admin',
        ];

        $adminCabangPermissions = [
            'dashboard-view-admin',
            'karyawan-view-admin', 'karyawan-create-admin', 'karyawan-edit-admin', 'karyawan-delete-admin', 'karyawan-export-excel-admin',
            'jabatan-view-admin', 'departemen-view-admin', 'cabang-view-admin',
            'cuti-view-admin', 'cuti-create-admin', 'cuti-edit-admin', 'cuti-delete-admin',
            'presensi-monitoring-view-admin', 'pengajuan-izin-view-admin', 'pengajuan-izin-approve-admin',
            'lembur-view-admin', 'lembur-approve-admin', 'dinas-luar-view-admin', 'dinas-luar-approve-admin',
            'kpi-view-admin', 'kpi-create-admin', 'kpi-edit-admin', 'kpi-delete-admin',
            'kenaikan-gaji-view-admin', 'bpjs-view-admin',
            'surat-peringatan-view-admin', 'surat-peringatan-manage-admin', 'laporan-view-admin',
            'jam-kerja-view-admin', 'jam-kerja-create-admin', 'jam-kerja-edit-admin', 'jam-kerja-delete-admin',
            'jam-kerja-dept-view-admin', 'jam-kerja-dept-create-admin', 'jam-kerja-dept-edit-admin', 'jam-kerja-dept-delete-admin',
        ];

        /*
        |--------------------------------------------------------------------------
        | EKSEKUSI SEEDER
        |--------------------------------------------------------------------------
        */

        // 1. Kumpulkan semua permission dari seluruh role untuk di-create (mencegah ada permission yang terlewat)
        $allPermissions = array_unique(array_merge(
            $adminPermissions,
            $hrdPermissions,
            $ownerPermissions,
            $adminCabangPermissions
        ));

        // 2. Insert ke table permissions
        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'user']);
        }

        // 3. Mapping nama role dengan array permission-nya
        $roles = [
            'administrator' => $adminPermissions,
            'hrd' => $hrdPermissions,
            'owner' => $ownerPermissions,
            'admin cabang' => $adminCabangPermissions,
        ];

        // 4. Looping untuk create/update Role & Sync Permission
        foreach ($roles as $roleName => $permissions) {
            Role::updateOrCreate(
                ['name' => $roleName, 'guard_name' => 'user']
            )->syncPermissions($permissions);
        }

        /*
        |--------------------------------------------------------------------------
        | GUARD: KARYAWAN
        |--------------------------------------------------------------------------
        */

        $leaderPermissions = [
            'dashboard-view-karyawan', 'presensi-create-karyawan', 'presensi-history-view-karyawan',
            'profile-view-karyawan', 'profile-edit-karyawan', 'izin-view-karyawan', 'izin-create-karyawan',
            'izin-edit-karyawan', 'izin-delete-karyawan', 'lembur-view-karyawan', 'lembur-create-karyawan',
            'kpi-input-karyawan', 'dinasluar-view-karyawan', 'dinasluar-create-karyawan', 'kpi-approve-karyawan',
            'kenaikan_gaji-view-karyawan',
        ];

        $pjoPermissions = [
            'dashboard-view-karyawan', 'profile-view-karyawan', 'profile-edit-karyawan',
            'kpi-input-karyawan', 'kpi-approve-karyawan', 'kenaikan_gaji-view-karyawan',
        ];

        $staffPermissions = [
            'dashboard-view-karyawan', 'presensi-create-karyawan', 'presensi-history-view-karyawan',
            'profile-view-karyawan', 'profile-edit-karyawan', 'izin-view-karyawan', 'izin-create-karyawan',
            'izin-edit-karyawan', 'izin-delete-karyawan', 'lembur-view-karyawan', 'lembur-create-karyawan',
            'kpi-input-karyawan', 'dinasluar-view-karyawan', 'dinasluar-create-karyawan',
            'kenaikan_gaji-view-karyawan',
        ];

        $allKaryawanPermissions = array_unique(array_merge($leaderPermissions, $pjoPermissions, $staffPermissions));

        foreach ($allKaryawanPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'karyawan']);
        }

        $karyawanRoles = [
            'staff' => $staffPermissions,
            'pjo' => $pjoPermissions,
            'spv' => $leaderPermissions,
            'kepala bagian' => $leaderPermissions,
        ];

        foreach ($karyawanRoles as $roleName => $permissions) {
            Role::updateOrCreate(
                ['name' => $roleName, 'guard_name' => 'karyawan']
            )->syncPermissions($permissions);
        }
    }
}
