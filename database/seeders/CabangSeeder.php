<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CabangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dataCabang = [
            [
                'kode_cabang' => 'HOJKT01',
                'nama_cabang' => 'Head Office Jakarta',
                'lokasi_kantor' => '-6.227296679794419, 106.80668182438204',
                'radius' => 100,
            ],
            [
                'kode_cabang' => 'CBNG0005',
                'nama_cabang' => 'PT. PERSADA ATLAS SAMUDERA',
                'lokasi_kantor' => '-3.9239409510113625, 102.28013385626741',
                'radius' => 1000,
            ],
            [
                'kode_cabang' => 'CBNG0009',
                'nama_cabang' => 'PT. SELAMAT JAYA RESOURCES',
                'lokasi_kantor' => '-3.7810868959589463, 102.31682163602754',
                'radius' => 150,
            ],
            [
                'kode_cabang' => 'CBNG0011',
                'nama_cabang' => 'PT. SKORD MINING',
                'lokasi_kantor' => '-3.7810868959589463, 102.31682163602754',
                'radius' => 150,
            ],
            [
                'kode_cabang' => 'CBNG0004',
                'nama_cabang' => 'PT. SAMUDRA ATLAS SEJAHTERA',
                'lokasi_kantor' => '-3.9239758878975275, 102.28012711320757',
                'radius' => 20,
            ],
            [
                'kode_cabang' => 'CBNG0006',
                'nama_cabang' => 'wndev Konstruksi',
                'lokasi_kantor' => '-3.781170845255156, 102.3167987777119',
                'radius' => 30,
            ],
            [
                'kode_cabang' => 'CBNG0001',
                'nama_cabang' => 'wndev',
                'lokasi_kantor' => '-3.7812597146924785, 102.31705329402465',
                'radius' => 100,
            ],
            [
                'kode_cabang' => 'CBNG0007',
                'nama_cabang' => 'wndev Pratama',
                'lokasi_kantor' => '-3.7810868959589463, 102.31682163602754',
                'radius' => 100,
            ],
            [
                'kode_cabang' => 'CBNG0008',
                'nama_cabang' => 'wndev Energi',
                'lokasi_kantor' => '-3.7810868959589463, 102.31682163602754',
                'radius' => 100,
            ],
            [
                'kode_cabang' => 'CBNG0010',
                'nama_cabang' => 'PT. SPEK',
                'lokasi_kantor' => '-3.7810868959589463, 102.31682163602754',
                'radius' => 100,
            ],
            [
                'kode_cabang' => 'CBNG0002',
                'nama_cabang' => 'wndev Site SSKB',
                'lokasi_kantor' => '-1.9517749116109366, 103.03197675101389',
                'radius' => 5000,
            ],
            [
                'kode_cabang' => 'CBNG0003',
                'nama_cabang' => 'wndev Site INJATAMA',
                'lokasi_kantor' => '-3.2550007475785905, 101.84215105616725',
                'radius' => 1000,
            ],
            [
                'kode_cabang' => 'CSI',
                'nama_cabang' => 'Cakrawala Solusi Indotama',
                'lokasi_kantor' => '-3.781493, 102.316847',
                'radius' => 100,
            ],
            [
                'kode_cabang' => 'RBJ',
                'nama_cabang' => 'PT. Rafflesia Bara Juara',
                'lokasi_kantor' => '-3.781493, 102.316847',
                'radius' => 100,
            ],
            [
                'kode_cabang' => 'TBKR',
                'nama_cabang' => 'Tambang Kuari BTJ',
                'lokasi_kantor' => '-3.406020, 102.527826',
                'radius' => 250,
            ],
            [
                'kode_cabang' => 'GLOBAL',
                'nama_cabang' => 'Anywhere',
                'lokasi_kantor' => '-3.7811081153537165, 102.31691438370122',
                'radius' => 999999,
            ],
        ];

        foreach ($dataCabang as $cabang) {
            DB::table('cabang')->updateOrInsert(
                ['kode_cabang' => $cabang['kode_cabang']],
                [
                    'nama_cabang' => $cabang['nama_cabang'],
                    'lokasi_kantor' => $cabang['lokasi_kantor'],
                    'radius' => $cabang['radius'],
                ]
            );
        }
    }
}
