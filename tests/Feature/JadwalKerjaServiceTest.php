<?php

namespace Tests\Feature;

use App\Services\JadwalKerjaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JadwalKerjaServiceTest extends TestCase
{
    use RefreshDatabase;

    private JadwalKerjaService $jadwal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->jadwal = new JadwalKerjaService;

        DB::table('cabang')->insert(['kode_cabang' => 'CBG1', 'nama_cabang' => 'Cabang 1', 'lokasi_kantor' => '0,0', 'radius' => 100]);
        DB::table('departemen')->insert(['kode_dept' => 'OPS', 'nama_dept' => 'Operasional']);
        DB::table('jam_kerja')->insert([
            ['kode_jam_kerja' => 'JK01', 'nama_jam_kerja' => 'Pagi', 'awal_jam_masuk' => '06:00', 'jam_masuk' => '07:00', 'akhir_jam_masuk' => '08:00', 'jam_pulang' => '16:00'],
            ['kode_jam_kerja' => 'JK02', 'nama_jam_kerja' => 'Siang', 'awal_jam_masuk' => '12:00', 'jam_masuk' => '13:00', 'akhir_jam_masuk' => '14:00', 'jam_pulang' => '21:00'],
        ]);
        DB::table('konfigurasi_jk_dept')->insert(['kode_jk_dept' => 'JKD1', 'kode_cabang' => 'CBG1', 'kode_dept' => 'OPS']);
        DB::table('konfigurasi_jk_dept_detail')->insert([
            ['kode_jk_dept' => 'JKD1', 'hari' => 'Senin', 'kode_jam_kerja' => 'JK01'],
            ['kode_jk_dept' => 'JKD1', 'hari' => 'Minggu', 'kode_jam_kerja' => null],
        ]);
        DB::table('karyawan')->insert([
            ['nik' => '1001', 'nama_lengkap' => 'A', 'nama_panggilan' => 'A', 'no_hp' => '0', 'password' => 'x', 'kode_dept' => 'OPS', 'kode_cabang' => 'CBG1'],
            ['nik' => '1002', 'nama_lengkap' => 'B', 'nama_panggilan' => 'B', 'no_hp' => '0', 'password' => 'x', 'kode_dept' => null, 'kode_cabang' => null],
        ]);
    }

    public function test_nama_hari(): void
    {
        $this->assertSame('Senin', $this->jadwal->namaHari('Mon'));
        $this->assertSame('Minggu', $this->jadwal->namaHari('Sun'));
    }

    public function test_jadwal_departemen_dipakai_jika_tidak_ada_jadwal_personal(): void
    {
        [$jamKerja, $libur, $sumber] = $this->jadwal->untukHari('1001', 'OPS', 'CBG1', 'Senin');

        $this->assertSame('JK01', $jamKerja->kode_jam_kerja);
        $this->assertFalse($libur);
        $this->assertSame('dept', $sumber);
    }

    public function test_jadwal_personal_mengalahkan_jadwal_departemen(): void
    {
        DB::table('konfigurasi_jamkerja')->insert(['nik' => '1001', 'hari' => 'Senin', 'kode_jam_kerja' => 'JK02']);

        [$jamKerja, , $sumber] = $this->jadwal->untukHari('1001', 'OPS', 'CBG1', 'senin');

        $this->assertSame('JK02', $jamKerja->kode_jam_kerja);
        $this->assertSame('personal', $sumber);
    }

    public function test_hari_tanpa_jam_kerja_dianggap_libur(): void
    {
        [, $libur] = $this->jadwal->untukHari('1001', 'OPS', 'CBG1', 'Minggu');

        $this->assertTrue($libur);
    }

    public function test_karyawan_tanpa_departemen_tetap_dapat_jadwal_personal(): void
    {
        DB::table('konfigurasi_jamkerja')->insert(['nik' => '1002', 'hari' => 'Senin', 'kode_jam_kerja' => 'JK01']);

        [$jamKerja, , $sumber] = $this->jadwal->untukHari('1002', null, null, 'Senin');

        $this->assertSame('JK01', $jamKerja->kode_jam_kerja);
        $this->assertSame('personal', $sumber);
        $this->assertSame([null, false, 'none'], $this->jadwal->untukHari('9999', null, null, 'Senin'));
    }
}
