<?php

namespace Tests\Feature;

use App\Models\HariLibur;
use App\Models\Izin;
use App\Models\Karyawan;
use App\Services\IzinService;
use App\Support\CutiDatesMeta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IzinServiceTest extends TestCase
{
    use RefreshDatabase;

    private IzinService $service;

    private Karyawan $karyawan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(IzinService::class);

        DB::table('cabang')->insert(['kode_cabang' => 'CBG1', 'nama_cabang' => 'Cabang 1', 'lokasi_kantor' => '0,0', 'radius' => 100]);
        DB::table('departemen')->insert(['kode_dept' => 'OPS', 'nama_dept' => 'Operasional']);
        DB::table('master_cuti')->insert(['kode_cuti' => 'CTH', 'nama_cuti' => 'Cuti Tahunan', 'jml_hari' => 12]);
        $this->karyawan = Karyawan::create([
            'nik' => '1001', 'nama_lengkap' => 'A', 'nama_panggilan' => 'A', 'no_hp' => '0', 'password' => 'x',
            'kode_cabang' => 'CBG1', 'kode_dept' => 'OPS', 'status_aktif' => Karyawan::STATUS_AKTIF,
        ]);
    }

    private function cuti(string $kode, array $tanggal, int $status = 1): Izin
    {
        return Izin::create([
            'kode_izin' => $kode, 'nik' => '1001', 'status' => 'c', 'kode_cuti' => 'CTH', 'status_approved' => $status,
            'tgl_izin_dari' => min($tanggal), 'tgl_izin_sampai' => max($tanggal),
            'keterangan' => CutiDatesMeta::append('cuti', $tanggal),
        ]);
    }

    public function test_bentrok_hanya_pada_tanggal_yang_benar_benar_diajukan(): void
    {
        $this->cuti('IZ09260001', ['2026-09-01', '2026-09-03'], 0);

        $this->assertTrue($this->service->hasDateConflict('1001', collect(['2026-09-03'])));
        // Tanggal 2 berada di antara 1 & 3 tapi tidak diajukan (cuti tidak berurutan).
        $this->assertFalse($this->service->hasDateConflict('1001', collect(['2026-09-02'])));
        $this->assertFalse($this->service->hasDateConflict('1001', collect(['2026-09-03']), 'IZ09260001'));
    }

    public function test_cuti_terpakai_hanya_menghitung_hari_tercatat_dan_bukan_libur(): void
    {
        $this->cuti('IZ09260001', ['2026-09-01', '2026-09-02', '2026-09-03']);
        foreach (['2026-09-01', '2026-09-02', '2026-09-03'] as $tgl) {
            DB::table('presensi')->insert(['nik' => '1001', 'tgl_presensi' => $tgl, 'status' => 'c', 'foto_in' => '-', 'lokasi_in' => '-']);
        }
        HariLibur::create(['tanggal_libur' => '2026-09-02', 'keterangan' => 'Libur', 'jenis_libur' => 'nasional']);

        $this->assertSame(2, $this->service->cutiTakenDays($this->karyawan, 2026, 'CTH'));
        $this->assertSame(0, $this->service->cutiTakenDays($this->karyawan, 2026, 'CTH', 'IZ09260001'));
    }

    public function test_hari_libur_berlaku_sesuai_cabang_dan_departemen(): void
    {
        HariLibur::insert([
            ['tanggal_libur' => '2026-09-01', 'keterangan' => 'Umum', 'kode_cabang' => null, 'kode_dept' => null],
            ['tanggal_libur' => '2026-09-02', 'keterangan' => 'Cabang lain', 'kode_cabang' => 'CBG9', 'kode_dept' => null],
            ['tanggal_libur' => '2026-09-03', 'keterangan' => 'Dept ini', 'kode_cabang' => null, 'kode_dept' => 'OPS'],
            // Data lama: kode_cabang tanpa foreign key bisa berisi "Semua Cabang" atau daftar dipisah koma.
            ['tanggal_libur' => '2026-09-04', 'keterangan' => 'Data lama', 'kode_cabang' => 'Semua Cabang', 'kode_dept' => null],
            ['tanggal_libur' => '2026-09-05', 'keterangan' => 'Data lama', 'kode_cabang' => 'CBG7,CBG1', 'kode_dept' => null],
        ]);

        $this->assertSame(
            ['2026-09-01', '2026-09-03', '2026-09-04', '2026-09-05'],
            HariLibur::tanggalBerlaku('CBG1', 'OPS', '2026-09-01', '2026-09-30')
        );
        $this->assertTrue(HariLibur::isHariLibur('2026-09-04', 'CBG1', 'OPS'));
        $this->assertFalse(HariLibur::isHariLibur('2026-09-02', 'CBG1', 'OPS'));
    }

    public function test_generate_kode_izin(): void
    {
        $this->assertSame('IZ09260001', $this->service->generateKodeIzin(null, 'IZ0926', 4));
        $this->assertSame('IZ09260013', $this->service->generateKodeIzin('IZ09260012', 'IZ0926', 4));
        $this->assertSame('IZ10260001', $this->service->generateKodeIzin('IZ09260012', 'IZ1026', 4));
    }
}
