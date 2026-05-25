<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterCutiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dataCuti = [
            [
                'kode_cuti' => 'CTH',
                'nama_cuti' => 'Cuti Tahunan',
                'jml_hari' => 12
            ],
            [
                'kode_cuti' => 'CMH',
                'nama_cuti' => 'Cuti Melahirkan',
                'jml_hari' => 90
            ],
            [
                'kode_cuti' => 'CKH',
                'nama_cuti' => 'Cuti Khusus',
                'jml_hari' => 3
            ],
            [
                'kode_cuti' => 'CTM',
                'nama_cuti' => 'Cuti Keluarga Meninggal',
                'jml_hari' => 2
            ],
            [
                'kode_cuti' => 'CNN',
                'nama_cuti' => 'Cuti Menikah',
                'jml_hari' => 2
            ],
            [
                'kode_cuti' => 'CKA',
                'nama_cuti' => 'Cuti Khitan Anak',
                'jml_hari' => 1
            ],
            [
                'kode_cuti' => 'CIL',
                'nama_cuti' => 'Cuti Istri Lahiran',
                'jml_hari' => 2
            ],
        ];

        foreach ($dataCuti as $cuti) {
            DB::table('master_cuti')->updateOrInsert(
                ['kode_cuti' => $cuti['kode_cuti']],
                [
                    'nama_cuti' => $cuti['nama_cuti'],
                    'jml_hari' => $cuti['jml_hari'],
                ]
            );
        }
    }
}