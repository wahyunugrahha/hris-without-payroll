<?php

namespace Database\Seeders;

use App\Models\Jabatan;
use App\Models\Karyawan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class KaryawanSeeder extends Seeder
{
    public function run(): void
    {
        $dataKaryawan = [
            [
                'nik' => '123456',
                'nama_panggilan' => 'Testing',
                'nama_lengkap' => 'Akun Testing',
                'jabatan_name' => 'Project Manager',
                'no_hp' => '08563647234',
                'kode_dept' => 'DEV',
                'kode_cabang' => 'CBNG0001',
                'password' => Hash::make('123456'),
            ],
        ];

        foreach ($dataKaryawan as $data) {
            // 1. Cari Jabatan beserta Rolenya berdasarkan nama
            $jabatan = Jabatan::with('role')->where('nama_jabatan', $data['jabatan_name'])->first();

            if ($jabatan) {
                // 2. Buat atau Update Karyawan menggunakan Model
                $karyawan = Karyawan::updateOrCreate(
                    ['nik' => $data['nik']],
                    [
                        'nama_panggilan' => $data['nama_panggilan'],
                        'nama_lengkap' => $data['nama_lengkap'],
                        'jabatan_id' => $jabatan->id,
                        'no_hp' => $data['no_hp'],
                        'kode_dept' => $data['kode_dept'],
                        'kode_cabang' => $data['kode_cabang'],
                        'password' => $data['password'],
                    ]
                );

                // 3. Assign Role Spatie (Jika jabatan punya role yang terhubung)
                if ($jabatan->role) {
                    $karyawan->syncRoles([$jabatan->role->name]);
                }
            } else {
                $this->command->warn("Jabatan '{$data['jabatan_name']}' tidak ditemukan. Karyawan NIK {$data['nik']} di-skip.");
            }
        }

        $this->command->info('Data Karyawan berhasil diproses dan Role telah diberikan.');
    }
}
