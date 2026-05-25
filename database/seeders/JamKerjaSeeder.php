<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JamKerjaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'kode_jam_kerja' => 'JK02',
                'nama_jam_kerja' => 'Shift Siang',
                'awal_jam_masuk' => '12:30:00',
                'jam_masuk' => '13:00:00',
                'akhir_jam_masuk' => '13:15:00',
                'jam_pulang' => '21:00:00',
                'lintashari' => 0,
            ],
            [
                'kode_jam_kerja' => 'JK03',
                'nama_jam_kerja' => 'Shift Malam',
                'awal_jam_masuk' => '17:00:00',
                'jam_masuk' => '19:00:00',
                'akhir_jam_masuk' => '21:00:00',
                'jam_pulang' => '05:00:00',
                'lintashari' => 0,
            ],
            [
                'kode_jam_kerja' => 'JK05',
                'nama_jam_kerja' => 'Tambang Malam',
                'awal_jam_masuk' => '18:00:00',
                'jam_masuk' => '19:00:00',
                'akhir_jam_masuk' => '21:00:00',
                'jam_pulang' => '07:00:00',
                'lintashari' => 1,
            ],
            [
                'kode_jam_kerja' => 'JK01',
                'nama_jam_kerja' => 'Shift Pagi',
                'awal_jam_masuk' => '07:30:00',
                'jam_masuk' => '09:10:00',
                'akhir_jam_masuk' => '16:55:00',
                'jam_pulang' => '17:00:00',
                'lintashari' => 0,
            ],
            [
                'kode_jam_kerja' => 'JK04',
                'nama_jam_kerja' => 'Sabtu Pagi',
                'awal_jam_masuk' => '07:30:00',
                'jam_masuk' => '09:10:00',
                'akhir_jam_masuk' => '15:55:00',
                'jam_pulang' => '16:00:00',
                'lintashari' => 0,
            ],
            [
                'kode_jam_kerja' => 'HAST',
                'nama_jam_kerja' => 'HOHASABTU',
                'awal_jam_masuk' => '06:00:00',
                'jam_masuk' => '09:00:00',
                'akhir_jam_masuk' => '12:00:00',
                'jam_pulang' => '12:00:00',
                'lintashari' => 0,
            ],
            [
                'kode_jam_kerja' => 'MSAS',
                'nama_jam_kerja' => 'SASMALAM',
                'awal_jam_masuk' => '19:00:00',
                'jam_masuk' => '20:00:00',
                'akhir_jam_masuk' => '17:00:00', // Sesuai gambar, meskipun terlihat tidak urut
                'jam_pulang' => '18:00:00',
                'lintashari' => 1,
            ],
            [
                'kode_jam_kerja' => 'SA01',
                'nama_jam_kerja' => 'PT SAS',
                'awal_jam_masuk' => '08:00:00',
                'jam_masuk' => '08:15:00',
                'akhir_jam_masuk' => '17:00:00',
                'jam_pulang' => '17:00:00',
                'lintashari' => 0,
            ],
            [
                'kode_jam_kerja' => 'JK11',
                'nama_jam_kerja' => 'OPERATOR SPARE',
                'awal_jam_masuk' => '17:00:00',
                'jam_masuk' => '19:00:00',
                'akhir_jam_masuk' => '21:00:00',
                'jam_pulang' => '00:00:00',
                'lintashari' => 1,
            ],
            [
                'kode_jam_kerja' => 'IJ01',
                'nama_jam_kerja' => 'INJATAMA',
                'awal_jam_masuk' => '06:00:00',
                'jam_masuk' => '07:05:00',
                'akhir_jam_masuk' => '16:00:00',
                'jam_pulang' => '18:00:00',
                'lintashari' => 0,
            ],
            [
                'kode_jam_kerja' => 'TB01',
                'nama_jam_kerja' => 'Tambang Pagi',
                'awal_jam_masuk' => '06:00:00',
                'jam_masuk' => '07:15:00',
                'akhir_jam_masuk' => '09:00:00',
                'jam_pulang' => '17:30:00',
                'lintashari' => 0,
            ],
            [
                'kode_jam_kerja' => 'KT02',
                'nama_jam_kerja' => 'Kantor Injatama',
                'awal_jam_masuk' => '06:00:00',
                'jam_masuk' => '08:15:00',
                'akhir_jam_masuk' => '21:00:00',
                'jam_pulang' => '16:45:00',
                'lintashari' => 0,
            ],
            [
                'kode_jam_kerja' => 'SC01',
                'nama_jam_kerja' => 'Security malam',
                'awal_jam_masuk' => '16:30:00',
                'jam_masuk' => '17:00:00',
                'akhir_jam_masuk' => '19:00:00',
                'jam_pulang' => '07:00:00',
                'lintashari' => 1,
            ],
            [
                'kode_jam_kerja' => 'SC02',
                'nama_jam_kerja' => 'Security pagi',
                'awal_jam_masuk' => '06:00:00',
                'jam_masuk' => '07:05:00',
                'akhir_jam_masuk' => '07:35:00',
                'jam_pulang' => '17:00:00',
                'lintashari' => 0,
            ],
            [
                'kode_jam_kerja' => 'SC04',
                'nama_jam_kerja' => 'Security Senin',
                'awal_jam_masuk' => '11:00:00',
                'jam_masuk' => '12:00:00',
                'akhir_jam_masuk' => '15:00:00',
                'jam_pulang' => '07:00:00',
                'lintashari' => 1,
            ],
            [
                'kode_jam_kerja' => 'SC03',
                'nama_jam_kerja' => 'Security minggu',
                'awal_jam_masuk' => '16:30:00',
                'jam_masuk' => '17:00:00',
                'akhir_jam_masuk' => '22:00:00',
                'jam_pulang' => '12:00:00',
                'lintashari' => 1,
            ],
        ];

        foreach ($data as $d) {
            DB::table('jam_kerja')->updateOrInsert(
                ['kode_jam_kerja' => $d['kode_jam_kerja']],
                $d
            );
        }
    }
}