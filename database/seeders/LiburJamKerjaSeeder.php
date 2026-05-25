<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LiburJamKerjaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeder ini membuat data master jam kerja dengan kode LIBUR
     */
    public function run(): void
    {
        // Insert data jam kerja LIBUR
        DB::table('jam_kerja')->insert([
            'kode_jam_kerja' => 'LIBR',
            'nama_jam_kerja' => 'LIBUR',
            'awal_jam_masuk' => '00:00:00',
            'jam_masuk' => '00:00:00',
            'akhir_jam_masuk' => '00:00:00',
            'jam_pulang' => '00:00:00',
            'lintashari' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('Data jam kerja LIBUR berhasil ditambahkan!');
    }
}
