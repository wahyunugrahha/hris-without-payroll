<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KonfigurasiUmumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $konfigurasi = [
            [
                'key' => 'point_gaji_kantor',
                'value' => '500',
                'type' => 'number',
                'group' => 'Gaji & Bonus Performa',
                'description' => 'Batasan point minimal untuk absensi karyawan kantor',
            ],
            [
                'key' => 'point_gaji_tambang',
                'value' => '750',
                'type' => 'number',
                'group' => 'Gaji & Bonus Performa',
                'description' => 'Batasan point minimal untuk absensi karyawan tambang',
            ],
            [
                'key' => 'toleransi_keterlambatan',
                'value' => '10',
                'type' => 'number',
                'group' => 'Presensi',
                'description' => 'Toleransi keterlambatan absensi (dalam menit)',
            ],
            [
                'key' => 'cabang_tambang',
                'value' => 'CBNG0003,CBNG0011,RBJ,TBKR,CBNG0002',
                'type' => 'select-multiple',
                'group' => 'Cabang',
                'description' => 'Daftar cabang yang masuk kelompok Tambang/Site (karyawan di cabang ini memiliki aturan absensi tambang)',
            ],
            [
                'key' => 'sp_tambang_aktif',
                'value' => '0',
                'type' => 'select',
                'group' => 'Surat Peringatan',
                'description' => 'Pemberlakuan Fitur SP (Surat Peringatan) otomatis untuk kelompok Tambang',
            ],
        ];

        foreach ($konfigurasi as $k) {
            \App\Models\KonfigurasiUmum::updateOrCreate(
                ['key' => $k['key']],
                $k
            );
        }
    }
}
