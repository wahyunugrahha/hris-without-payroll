<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Jabatan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin IT',
                'email' => 'admin@wndev.com',
                'password' => Hash::make('Admin12345'),
                'kode_dept' => 'DEV',
                'kode_cabang' => null,
                'jabatan_name' => 'Administrator',
            ],
            [
                'name' => 'HRD',
                'email' => 'hrd@wndev.com',
                'password' => Hash::make('123456'),
                'kode_dept' => 'HRD',
                'kode_cabang' => 'CBNG0001',
                'jabatan_name' => 'HRD',
            ],
            [
                'name' => 'Karina',
                'email' => 'karina@wndev.com',
                'password' => Hash::make('123456'),
                'kode_dept' => 'HRD',
                'kode_cabang' => null,
                'jabatan_name' => 'HRD',
            ],
            [
                'name' => 'admincecan1',
                'email' => 'hradmin1@wndev.com',
                'password' => Hash::make('123456'),
                'kode_dept' => 'ADM',
                'kode_cabang' => 'GLOBAL',
                'jabatan_name' => 'HRD',
            ],
            [
                'name' => 'admincecan2',
                'email' => 'hradmin2@wndev.com',
                'password' => Hash::make('123456'),
                'kode_dept' => 'ADM',
                'kode_cabang' => 'GLOBAL',
                'jabatan_name' => 'HRD',
            ],
            [
                'name' => 'admincecan3',
                'email' => 'hradmin3@wndev.com',
                'password' => Hash::make('123456'),
                'kode_dept' => 'ADM',
                'kode_cabang' => 'GLOBAL',
                'jabatan_name' => 'HRD',
            ],
            [
                'name' => 'DEDENG MARCO SAPUTRA',
                'email' => 'owner@wndev.com',
                'password' => Hash::make('123456'),
                'kode_dept' => 'OWN',
                'kode_cabang' => null,
                'jabatan_name' => 'BOD',
            ],
            [
                'name' => 'HRD SITE KETAHUN',
                'email' => 'hrdketahun@wndev.com',
                'password' => Hash::make('123456'),
                'kode_dept' => 'HRD',
                'kode_cabang' => 'CBNG0003',
                'jabatan_name' => 'HRD Cabang',
            ],
            [
                'name' => 'HRD RBJ',
                'email' => 'hrdrbj@wndev.com',
                'password' => Hash::make('123456'),
                'kode_dept' => 'HRD',
                'kode_cabang' => 'RBJ',
                'jabatan_name' => 'HRD Cabang',
            ],
            [
                'name' => 'HRD SITE JAMBI',
                'email' => 'hrdjambi@wndev.com',
                'password' => Hash::make('123456'),
                'kode_dept' => 'HRD',
                'kode_cabang' => 'CBNG0002',
                'jabatan_name' => 'HRD Cabang',
            ],
            [
                'name' => 'HRD PULAU BAAI',
                'email' => 'hrdsas@wndev.com',
                'password' => Hash::make('123456'),
                'kode_dept' => 'HRD',
                'kode_cabang' => 'CBNG0004',
                'jabatan_name' => 'HRD Cabang',
            ],
            [
                'name' => 'Widia HR Payroll',
                'email' => 'widia@wndev.com',
                'password' => Hash::make('123456'),
                'kode_dept' => 'HRD',
                'kode_cabang' => null,
                'jabatan_name' => 'HRD',
            ],
            [
                'name' => 'tes',
                'email' => 'tes@wndev.com',
                'password' => Hash::make('123456'),
                'kode_dept' => 'HRD',
                'kode_cabang' => null,
                'jabatan_name' => 'HRD',
            ],
        ];

        foreach ($users as $userData) {
            $jabatan = Jabatan::with('role')->where('nama_jabatan', $userData['jabatan_name'])->first();

            if ($jabatan) {
                $user = User::updateOrCreate(
                    ['email' => $userData['email']],
                    [
                        'name' => $userData['name'],
                        'password' => $userData['password'],
                        'kode_dept' => $userData['kode_dept'],
                        'kode_cabang' => $userData['kode_cabang'],
                        'jabatan_id' => $jabatan->id,
                        'email_verified_at' => now(),
                    ]
                );

                if ($jabatan->role) {
                    $user->syncRoles([$jabatan->role->name]);
                }
            }
        }
    }
}
