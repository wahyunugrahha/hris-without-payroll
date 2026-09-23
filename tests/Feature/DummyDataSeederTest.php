<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DummyDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DummyDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_dummy_mengisi_data_dan_aman_dijalankan_ulang(): void
    {
        $this->seed([DatabaseSeeder::class, DummyDataSeeder::class]);
        $presensi = DB::table('presensi')->where('nik', 'like', '9000%')->count();

        $this->seed(DummyDataSeeder::class);

        $this->assertSame(30, DB::table('karyawan')->where('nik', 'like', '9000%')->count());
        $this->assertSame($presensi, DB::table('presensi')->where('nik', 'like', '9000%')->count());
        $this->assertGreaterThan(0, $presensi);
        $this->assertGreaterThan(0, DB::table('izin')->where('nik', 'like', '9000%')->count());
    }
}
