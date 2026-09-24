<?php

namespace Tests\Feature;

use App\Models\Karyawan;
use App\Services\Dashboard\RekapKehadiranHarian;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Alpha hanya untuk karyawan wajib presensi yang terjadwal kerja dan tanpa catatan.
 * Senin 2026-09-21 hari kerja, Minggu 2026-09-20 tanpa jadwal (libur).
 */
class RekapKehadiranHarianTest extends TestCase
{
    use RefreshDatabase;

    private const SENIN = '2026-09-21';

    private const MINGGU = '2026-09-20';

    private array $semua = ['isAdminCabang' => false, 'kodeCabang' => null, 'filterCabang' => '', 'filterDept' => ''];

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('cabang')->insert(['kode_cabang' => 'CBG1', 'nama_cabang' => 'Cabang 1', 'lokasi_kantor' => '0,0', 'radius' => 100]);
        DB::table('departemen')->insert(['kode_dept' => 'OPS', 'nama_dept' => 'Operasional']);
        DB::table('jam_kerja')->insert(['kode_jam_kerja' => 'JK01', 'nama_jam_kerja' => 'Pagi', 'awal_jam_masuk' => '06:00', 'jam_masuk' => '08:00', 'akhir_jam_masuk' => '09:00', 'jam_pulang' => '17:00']);
        DB::table('konfigurasi_jk_dept')->insert(['kode_jk_dept' => 'JKD1', 'kode_cabang' => 'CBG1', 'kode_dept' => 'OPS']);
        DB::table('konfigurasi_jk_dept_detail')->insert(['kode_jk_dept' => 'JKD1', 'hari' => 'Senin', 'kode_jam_kerja' => 'JK01']);

        foreach (['1001', '1002', '1003', '1004', '1005'] as $nik) {
            $this->karyawan($nik);
        }
        $this->karyawan('1006', ['is_whitelist' => 1]);                        // dikecualikan dari presensi
        $this->karyawan('1007', ['status_aktif' => Karyawan::STATUS_NONAKTIF]); // sudah tidak aktif
    }

    private function karyawan(string $nik, array $attrs = []): void
    {
        Karyawan::create(array_merge([
            'nik' => $nik, 'nama_lengkap' => "K$nik", 'nama_panggilan' => "K$nik", 'no_hp' => '0', 'password' => 'x',
            'kode_cabang' => 'CBG1', 'kode_dept' => 'OPS', 'status_aktif' => Karyawan::STATUS_AKTIF,
        ], $attrs));
    }

    private function presensi(string $nik, string $tgl, string $status, string $jamIn = '07:45:00'): void
    {
        DB::table('presensi')->insert([
            'nik' => $nik, 'tgl_presensi' => $tgl, 'jam_in' => $status === 'h' ? $jamIn : '00:00:00',
            'foto_in' => '-', 'lokasi_in' => '-', 'kode_jam_kerja' => 'JK01', 'status' => $status,
        ]);
    }

    private function rekap(string $tanggal, string $sekarang): array
    {
        return app(RekapKehadiranHarian::class)->untuk([$tanggal], $this->semua, Carbon::parse($sekarang))[$tanggal];
    }

    public function test_hari_kerja_yang_sudah_lewat(): void
    {
        $this->presensi('1001', self::SENIN, 'h');                 // hadir tepat waktu
        $this->presensi('1002', self::SENIN, 'h', '08:30:00');     // terlambat
        $this->presensi('1003', self::SENIN, 's');                 // sakit (izin disetujui)
        DB::table('dinas_luar')->insert(['nik' => '1004', 'tgl_mulai' => self::SENIN, 'tgl_selesai' => self::SENIN, 'alasan' => 'x', 'status_acc' => 'acc']);
        // 1005 tanpa catatan -> alpha; 1006 whitelist & 1007 nonaktif tidak dihitung.

        $r = $this->rekap(self::SENIN, '2026-09-22 10:00');

        $this->assertSame(5, $r['wajib']);
        $this->assertSame(5, $r['dijadwalkan']);
        $this->assertSame(2, $r['hadir']);
        $this->assertSame(1, $r['terlambat']);
        $this->assertSame(1, $r['sakit']);
        $this->assertSame(1, $r['dinas_luar']);
        $this->assertSame(1, $r['alpha']);
    }

    public function test_hari_libur_tidak_menghasilkan_alpha(): void
    {
        $r = $this->rekap(self::MINGGU, '2026-09-22 10:00');

        $this->assertSame(0, $r['alpha']);
        $this->assertSame(5, $r['libur']);
    }

    public function test_libur_nasional_tidak_menghasilkan_alpha(): void
    {
        DB::table('hari_libur')->insert(['tanggal_libur' => self::SENIN, 'keterangan' => 'Libur nasional']);

        $r = $this->rekap(self::SENIN, '2026-09-22 10:00');

        $this->assertSame(0, $r['alpha']);
        $this->assertSame(5, $r['libur']);
    }

    public function test_hari_ini_sebelum_batas_jam_masuk_belum_absen_bukan_alpha(): void
    {
        $this->presensi('1001', self::SENIN, 'h');

        $pagi = $this->rekap(self::SENIN, self::SENIN.' 08:30');
        $this->assertSame(4, $pagi['belum_absen']);
        $this->assertSame(0, $pagi['alpha']);

        $siang = $this->rekap(self::SENIN, self::SENIN.' 10:00');
        $this->assertSame(0, $siang['belum_absen']);
        $this->assertSame(4, $siang['alpha']);
    }

    public function test_presensi_dianulir_dianggap_tidak_hadir(): void
    {
        $this->presensi('1001', self::SENIN, 'x');

        $r = $this->rekap(self::SENIN, '2026-09-22 10:00');

        $this->assertSame(0, $r['hadir']);
        $this->assertSame(5, $r['alpha']);
    }
}
