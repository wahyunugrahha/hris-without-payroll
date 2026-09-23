<?php

namespace Tests\Feature;

use App\Models\Izin;
use App\Models\Karyawan;
use App\Models\Presensi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Pengaman refactor: ketiga dashboard admin harus tetap ter-render (tanpa error / variabel view hilang)
 * untuk admin pusat maupun admin cabang, dengan sedikit data di hari ini.
 */
class DashboardSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('cabang')->insert([
            ['kode_cabang' => 'CBG1', 'nama_cabang' => 'Cabang 1', 'lokasi_kantor' => '0,0', 'radius' => 100],
            ['kode_cabang' => 'CBNG0003', 'nama_cabang' => 'Site Tambang', 'lokasi_kantor' => '0,0', 'radius' => 100],
        ]);
        DB::table('departemen')->insert(['kode_dept' => 'OPS', 'nama_dept' => 'Operasional']);
        DB::table('jam_kerja')->insert(['kode_jam_kerja' => 'JK01', 'nama_jam_kerja' => 'Normal', 'awal_jam_masuk' => '06:00', 'jam_masuk' => '08:00', 'akhir_jam_masuk' => '09:00', 'jam_pulang' => '17:00']);

        foreach (['1001' => 'CBG1', '2001' => 'CBNG0003'] as $nik => $cabang) {
            Karyawan::create([
                'nik' => $nik, 'nama_lengkap' => "Karyawan $nik", 'nama_panggilan' => "K$nik", 'no_hp' => '0',
                'password' => 'x', 'kode_cabang' => $cabang, 'kode_dept' => 'OPS', 'jenis_kelamin' => 'L',
                'tanggal_lahir' => '1995-01-01', 'tmt' => '2024-01-01', 'status_aktif' => Karyawan::STATUS_AKTIF,
            ]);
        }

        Presensi::create([
            'nik' => '1001', 'tgl_presensi' => now()->toDateString(), 'status' => 'h', 'jam_in' => '08:30:00',
            'foto_in' => '-', 'lokasi_in' => '0,0', 'kode_jam_kerja' => 'JK01',
        ]);
        Izin::create([
            'kode_izin' => 'IZ00000001', 'nik' => '2001', 'status' => 's', 'status_approved' => 0,
            'tgl_izin_dari' => now()->toDateString(), 'tgl_izin_sampai' => now()->toDateString(), 'keterangan' => 'Demam',
        ]);

        Permission::create(['name' => 'dashboard-view-admin', 'guard_name' => 'user']);
        Role::create(['name' => 'admin cabang', 'guard_name' => 'user']);
        $this->admin = User::create(['name' => 'HRD', 'email' => 'hrd@test.id', 'password' => Hash::make('rahasia123')]);
        $this->admin->givePermissionTo('dashboard-view-admin');
    }

    public function test_dashboard_admin_pusat(): void
    {
        $this->actingAs($this->admin, 'user')->get('/panel/dashboardadmin')->assertOk();
    }

    public function test_dashboard_admin_cabang_dengan_filter(): void
    {
        $admin = User::create(['name' => 'Admin Cabang', 'email' => 'ac@test.id', 'password' => Hash::make('rahasia123'), 'kode_cabang' => 'CBG1']);
        $admin->assignRole('admin cabang')->givePermissionTo('dashboard-view-admin');

        $this->actingAs($admin, 'user')->get('/panel/dashboardadmin?q=karyawan&dept=OPS')->assertOk();
    }

    public function test_dashboard_overview_dan_filter_periode(): void
    {
        $this->actingAs($this->admin, 'user')->get('/overview')->assertOk()->assertViewHas('periodeOptions');
        $this->actingAs($this->admin, 'user')->get('/overview?periode='.now()->subMonth()->format('Y-m-26'))->assertOk();
    }

    public function test_dashboard_tv(): void
    {
        $this->actingAs($this->admin, 'user')->get('/dashboard-tv')->assertOk()->assertViewHas('regToken');
    }
}
