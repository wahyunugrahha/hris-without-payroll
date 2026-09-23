<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Izin;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\KPIDaily;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Regression test untuk temuan audit keamanan (K-01 s/d K-04, T-02).
 */
class SecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    private function karyawan(string $nik, array $attrs = []): Karyawan
    {
        return Karyawan::create(array_merge([
            'nik' => $nik,
            'nama_lengkap' => 'Karyawan '.$nik,
            'nama_panggilan' => $nik,
            'no_hp' => '0800000000',
            'password' => Hash::make('rahasia123'),
            'status_aktif' => Karyawan::STATUS_AKTIF,
        ], $attrs));
    }

    public function test_dashboard_tv_dan_overview_tidak_bisa_dibuka_tanpa_login(): void
    {
        $this->get('/dashboard-tv')->assertRedirect(route('loginadmin'));
        $this->get('/overview')->assertRedirect(route('loginadmin'));
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    private function bolehEditIzin(Karyawan $karyawan): Karyawan
    {
        Permission::findOrCreate('izin-edit-karyawan', 'karyawan');

        return $karyawan->givePermissionTo('izin-edit-karyawan');
    }

    public function test_karyawan_tidak_bisa_mengubah_izin_milik_orang_lain(): void
    {
        $a = $this->bolehEditIzin($this->karyawan('1001'));
        $b = $this->karyawan('1002');

        $izinB = Izin::create([
            'kode_izin' => 'IZ09260001', 'nik' => $b->nik, 'status' => 'i', 'status_approved' => 0,
            'tgl_izin_dari' => '2026-09-01', 'tgl_izin_sampai' => '2026-09-01', 'keterangan' => 'asli',
        ]);

        $this->actingAs($a, 'karyawan')
            ->put("/pengajuanizin/{$izinB->kode_izin}/updateizinabsen", [
                'dari' => '2026-09-10', 'sampai' => '2026-09-12', 'keterangan' => 'diubah orang lain',
            ])
            ->assertSessionHas('error');

        $this->assertSame('asli', $izinB->fresh()->keterangan);
    }

    public function test_karyawan_bisa_mengubah_izin_miliknya_yang_masih_pending(): void
    {
        $a = $this->bolehEditIzin($this->karyawan('1001'));
        $izin = Izin::create([
            'kode_izin' => 'IZ09260002', 'nik' => $a->nik, 'status' => 'i', 'status_approved' => 0,
            'tgl_izin_dari' => '2026-09-01', 'tgl_izin_sampai' => '2026-09-01', 'keterangan' => 'awal',
        ]);

        $this->actingAs($a, 'karyawan')
            ->put("/pengajuanizin/{$izin->kode_izin}/updateizinabsen", [
                'dari' => '2026-09-02', 'sampai' => '2026-09-03', 'keterangan' => 'revisi',
            ])
            ->assertSessionHas('success');

        $this->assertSame('revisi', $izin->fresh()->keterangan);
    }

    public function test_karyawan_bukan_atasan_tidak_bisa_reject_kpi(): void
    {
        Permission::create(['name' => 'kpi-approve-karyawan', 'guard_name' => 'karyawan']);
        $role = Role::create(['name' => 'staff', 'guard_name' => 'karyawan']);
        $jabatan = Jabatan::create(['nama_jabatan' => 'Staff', 'role_id' => $role->id]);

        $a = $this->karyawan('1001', ['jabatan_id' => $jabatan->id]);
        $b = $this->karyawan('1002', ['jabatan_id' => $jabatan->id]);
        $a->givePermissionTo('kpi-approve-karyawan');

        $kpi = KPIDaily::create(['nik' => $b->nik, 'tanggal' => '2026-09-01', 'status' => 'submitted']);

        $this->actingAs($a, 'karyawan')
            ->post("/kpi/{$kpi->id}/reject", ['alasan_reject' => 'sabotase'])
            ->assertSessionHas('error');

        $this->assertSame('submitted', $kpi->fresh()->status);
    }

    public function test_admin_cabang_tidak_bisa_mengubah_cabangnya_sendiri(): void
    {
        Cabang::create(['kode_cabang' => 'CBG1', 'nama_cabang' => 'Cabang 1', 'lokasi_kantor' => '0,0', 'radius' => 100]);
        Permission::create(['name' => 'dashboard-view-admin', 'guard_name' => 'user']);
        Role::create(['name' => 'admin cabang', 'guard_name' => 'user']);

        $admin = User::create([
            'name' => 'Admin Cabang', 'email' => 'admin@cabang.test',
            'password' => Hash::make('rahasia123'), 'kode_cabang' => 'CBG1',
        ]);
        $admin->assignRole('admin cabang');
        $admin->givePermissionTo('dashboard-view-admin');

        $this->actingAs($admin, 'user')
            ->put('/panel/account', [
                'name' => 'Admin Cabang', 'email' => 'admin@cabang.test', 'kode_cabang' => '',
            ])
            ->assertSessionHas('success');

        $this->assertSame('CBG1', $admin->fresh()->kode_cabang);
    }

    public function test_ganti_password_admin_wajib_password_lama(): void
    {
        Permission::create(['name' => 'dashboard-view-admin', 'guard_name' => 'user']);
        $admin = User::create(['name' => 'Admin', 'email' => 'a@a.test', 'password' => Hash::make('rahasia123')]);
        $admin->givePermissionTo('dashboard-view-admin');

        $this->actingAs($admin, 'user')
            ->put('/panel/account', [
                'name' => 'Admin', 'email' => 'a@a.test',
                'password' => 'passwordbaru1', 'password_confirmation' => 'passwordbaru1',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('rahasia123', $admin->fresh()->password));
    }

    public function test_karyawan_dengan_password_default_dipaksa_ganti_password(): void
    {
        $a = $this->karyawan('1001', ['must_change_password' => true]);

        $this->actingAs($a, 'karyawan')
            ->get('/dashboard')
            ->assertRedirect(route('karyawan.profile.edit'));
    }
}
