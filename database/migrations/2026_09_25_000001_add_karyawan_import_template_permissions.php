<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Rute import & unduh template karyawan memakai permission yang belum pernah dibuat,
 * sehingga selalu 403. Buat permission-nya dan berikan ke role yang boleh menambah karyawan.
 */
return new class extends Migration
{
    private const PERMISSIONS = ['karyawan-import-excel-admin', 'karyawan-download-template-admin'];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $nama) {
            Permission::firstOrCreate(['name' => $nama, 'guard_name' => 'user']);
        }

        Role::where('guard_name', 'user')
            ->whereHas('permissions', fn ($q) => $q->where('name', 'karyawan-create-admin'))
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo(self::PERMISSIONS));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', self::PERMISSIONS)->where('guard_name', 'user')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
