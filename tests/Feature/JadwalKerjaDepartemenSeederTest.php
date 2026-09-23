<?php

namespace Tests\Feature;

use App\Services\JadwalKerjaService;
use Database\Seeders\JadwalKerjaDepartemenSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JadwalKerjaDepartemenSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('cabang')->insert([
            ['kode_cabang' => 'CBG1', 'nama_cabang' => 'Cabang 1', 'lokasi_kantor' => '0,0', 'radius' => 100],
            ['kode_cabang' => 'CBG2', 'nama_cabang' => 'Cabang 2', 'lokasi_kantor' => '0,0', 'radius' => 100],
        ]);
        DB::table('departemen')->insert([
            ['kode_dept' => 'OPS', 'nama_dept' => 'Operasional'],
            ['kode_dept' => 'HRD', 'nama_dept' => 'HRD'],
        ]);
        DB::table('jam_kerja')->insert([
            ['kode_jam_kerja' => 'JK01', 'nama_jam_kerja' => 'Shift Pagi', 'awal_jam_masuk' => '07:30', 'jam_masuk' => '09:10', 'akhir_jam_masuk' => '16:55', 'jam_pulang' => '17:00'],
            ['kode_jam_kerja' => 'JK04', 'nama_jam_kerja' => 'Sabtu Pagi', 'awal_jam_masuk' => '07:30', 'jam_masuk' => '09:10', 'akhir_jam_masuk' => '15:55', 'jam_pulang' => '16:00'],
            ['kode_jam_kerja' => 'TB01', 'nama_jam_kerja' => 'Tambang Pagi', 'awal_jam_masuk' => '06:00', 'jam_masuk' => '07:15', 'akhir_jam_masuk' => '09:00', 'jam_pulang' => '17:30'],
        ]);
    }

    public function test_semua_cabang_departemen_mendapat_jadwal_default(): void
    {
        $this->seed(JadwalKerjaDepartemenSeeder::class);

        $this->assertSame(4, DB::table('konfigurasi_jk_dept')->count());
        $this->assertSame(28, DB::table('konfigurasi_jk_dept_detail')->count());

        $jadwal = app(JadwalKerjaService::class);
        $this->assertSame('JK01', $jadwal->untukHari('1001', 'OPS', 'CBG2', 'Senin')[0]->kode_jam_kerja);
        $this->assertSame('JK04', $jadwal->untukHari('1001', 'OPS', 'CBG2', 'Sabtu')[0]->kode_jam_kerja);
        $this->assertTrue($jadwal->untukHari('1001', 'OPS', 'CBG2', 'Minggu')[1]);
    }

    public function test_jadwal_yang_sudah_diatur_tidak_ditimpa_dan_aman_diulang(): void
    {
        DB::table('konfigurasi_jk_dept')->insert(['kode_jk_dept' => 'JKDCBG1OPS', 'kode_cabang' => 'CBG1', 'kode_dept' => 'OPS']);
        DB::table('konfigurasi_jk_dept_detail')->insert(['kode_jk_dept' => 'JKDCBG1OPS', 'hari' => 'Senin', 'kode_jam_kerja' => 'TB01']);

        $this->seed(JadwalKerjaDepartemenSeeder::class);
        $this->seed(JadwalKerjaDepartemenSeeder::class);

        $this->assertSame(4, DB::table('konfigurasi_jk_dept')->count());
        $this->assertSame(
            'TB01',
            app(JadwalKerjaService::class)->untukHari('1001', 'OPS', 'CBG1', 'Senin')[0]->kode_jam_kerja
        );
    }
}
