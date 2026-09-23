<?php

namespace Tests\Feature;

use App\Models\Karyawan;
use App\Models\Presensi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Alur absen masuk karyawan (tanpa bergantung jam: jadwal dibuat berlaku 00:00-23:59 setiap hari).
 */
class PresensiKaryawanTest extends TestCase
{
    use RefreshDatabase;

    private const FOTO_PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        DB::table('cabang')->insert(['kode_cabang' => 'CBG1', 'nama_cabang' => 'Cabang 1', 'lokasi_kantor' => '0,0', 'radius' => 100]);
        DB::table('jam_kerja')->insert([
            'kode_jam_kerja' => 'JK01', 'nama_jam_kerja' => 'Fleksibel', 'awal_jam_masuk' => '00:00',
            'jam_masuk' => '00:01', 'akhir_jam_masuk' => '23:59', 'jam_pulang' => '23:59', 'lintashari' => 0,
        ]);

        $karyawan = Karyawan::create([
            'nik' => '1001', 'nama_lengkap' => 'A', 'nama_panggilan' => 'A', 'no_hp' => '0', 'password' => 'x',
            'kode_cabang' => 'CBG1', 'status_aktif' => Karyawan::STATUS_AKTIF, 'is_whitelist' => 0,
        ]);
        foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $hari) {
            DB::table('konfigurasi_jamkerja')->insert(['nik' => '1001', 'hari' => $hari, 'kode_jam_kerja' => 'JK01']);
        }

        Permission::create(['name' => 'presensi-create-karyawan', 'guard_name' => 'karyawan']);
        $karyawan->givePermissionTo('presensi-create-karyawan');
        $this->actingAs($karyawan, 'karyawan');
    }

    private function absen(array $data)
    {
        return $this->postJson('/presensi/store', $data + ['absen_type' => 'in', 'image' => self::FOTO_PNG]);
    }

    public function test_absen_masuk_di_dalam_radius_tersimpan_beserta_foto(): void
    {
        $this->absen(['lokasi' => '0.0001,0.0001'])->assertOk()->assertJson(['success' => true]);

        $presensi = Presensi::sole();
        $this->assertSame('h', $presensi->status);
        $this->assertSame('JK01', $presensi->kode_jam_kerja);
        Storage::disk('public')->assertExists('uploads/absensi/'.$presensi->foto_in);
    }

    public function test_absen_di_luar_radius_ditolak(): void
    {
        $this->absen(['lokasi' => '0.01,0.01'])->assertForbidden()->assertJson(['success' => false]);

        $this->assertSame(0, Presensi::count());
    }

    public function test_foto_bukan_gambar_ditolak(): void
    {
        $this->absen(['lokasi' => '0,0', 'image' => 'data:image/png;base64,'.base64_encode('<?php echo 1;')])
            ->assertStatus(400);

        $this->assertSame(0, Presensi::count());
    }

    public function test_absen_masuk_dua_kali_ditolak(): void
    {
        $this->absen(['lokasi' => '0,0'])->assertOk();

        $this->absen(['lokasi' => '0,0'])->assertStatus(409);
        $this->assertSame(1, Presensi::count());
    }

    public function test_histori_tanpa_data_selesai_dimuat(): void
    {
        // Regresi: iterator tanggal immutable membuat loop harian tidak pernah berhenti (loading terus).
        foreach ([now()->subMonthNoOverflow(), now()] as $bulan) {
            $this->post('/presensi/gethistori', ['bulan' => $bulan->month, 'tahun' => $bulan->year])->assertOk();
        }
    }
}
