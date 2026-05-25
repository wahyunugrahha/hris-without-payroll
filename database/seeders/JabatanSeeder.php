<?php

namespace Database\Seeders;

use App\Models\Karyawan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class JabatanSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ambil ID role yang diperlukan
        $roles = Role::whereIn('name', [
            'owner',
            'administrator',
            'hrd',
            'admin cabang',
            'spv',
            'pjo',
            'kepala bagian',
            'staff'
        ])->pluck('id', 'name');

        // 2. Definisi Mapping Jabatan ke Role
        $jabatan = [
            // ADMIN / HRD
            ['nama_jabatan' => 'Administrator', 'role_name' => 'administrator'],
            ['nama_jabatan' => 'BOD', 'role_name' => 'owner'],
            ['nama_jabatan' => 'HRD', 'role_name' => 'hrd'],
            ['nama_jabatan' => 'HRD Cabang', 'role_name' => 'admin cabang'],

            // MANAGERIAL
            ['nama_jabatan' => 'Project Manager', 'role_name' => 'spv'],
            ['nama_jabatan' => 'Supervisor', 'role_name' => 'spv'],

            // PJO
            ['nama_jabatan' => 'PJO', 'role_name' => 'pjo'],

            // KEPALA BAGIAN
            ['nama_jabatan' => 'Kepala Gudang', 'role_name' => 'kepala bagian'],
            ['nama_jabatan' => 'Kepala Mekanik', 'role_name' => 'kepala bagian'],

            // STAFF / CREW (Explicit)
            ['nama_jabatan' => 'Staff', 'role_name' => 'staff'],
            ['nama_jabatan' => 'Staff HR', 'role_name' => 'staff'],
            ['nama_jabatan' => 'Staff Legal', 'role_name' => 'staff'],
            ['nama_jabatan' => 'Crew Mekanik', 'role_name' => 'staff'],
            ['nama_jabatan' => 'Inventory Auditor', 'role_name' => 'staff'],
        ];

        // 3. Proses Update Jabatan yang Terdefinisi (Whitelist)
        foreach ($jabatan as $item) {
            $roleId = $roles[$item['role_name']] ?? null;

            if ($roleId) {
                DB::table('jabatan')->updateOrInsert(
                    ['nama_jabatan' => $item['nama_jabatan']],
                    [
                        'role_id' => $roleId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        // 4. LOGIKA KETAT (Strict Mode)
        // Ambil ID role staff
        $staffRoleId = $roles['staff'] ?? null;

        if ($staffRoleId) {
            // Ambil semua nama jabatan yang sudah kita definisikan di atas (Whitelist)
            $knownJabatanNames = array_column($jabatan, 'nama_jabatan');

            // Update SEMUA jabatan di database yang NAMANYA TIDAK ADA di whitelist
            // Baik yang role_id-nya NULL maupun yang sudah ada isinya (reset paksa)
            DB::table('jabatan')
                ->whereNotIn('nama_jabatan', $knownJabatanNames)
                ->update([
                    'role_id' => $staffRoleId,
                    'updated_at' => now(),
                ]);
        }

        // 5. Set jabatan_id kosong ke jabatan staff, lalu assign role staff
        if ($staffRoleId) {
            $staffJabatanId = DB::table('jabatan')->where('nama_jabatan', 'Staff')->value('id');

            if ($staffJabatanId) {
                DB::table('karyawan')
                    ->whereNull('jabatan_id')
                    ->update([
                        'jabatan_id' => $staffJabatanId,
                        'updated_at' => now(),
                    ]);

                Karyawan::where('jabatan_id', $staffJabatanId)
                    ->select('nik')
                    ->chunkById(200, function ($karyawans) {
                        foreach ($karyawans as $karyawan) {
                            $karyawan->syncRoles(['staff']);
                        }
                    }, 'nik');
            }
        }
    }
}