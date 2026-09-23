<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\Lembur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Aturan tunggal: hanya role "admin cabang" yang dibatasi ke cabangnya.
 */
class CabangScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('cabang')->insert([
            ['kode_cabang' => 'CBG1', 'nama_cabang' => 'Cabang 1', 'lokasi_kantor' => '0,0', 'radius' => 100],
            ['kode_cabang' => 'CBG2', 'nama_cabang' => 'Cabang 2', 'lokasi_kantor' => '0,0', 'radius' => 100],
        ]);
        Role::create(['name' => 'admin cabang', 'guard_name' => 'user']);
        Role::create(['name' => 'hrd', 'guard_name' => 'user']);

        foreach (['1001' => 'CBG1', '2001' => 'CBG2'] as $nik => $cabang) {
            Karyawan::create([
                'nik' => $nik, 'nama_lengkap' => "K$nik", 'nama_panggilan' => "K$nik", 'no_hp' => '0',
                'password' => 'x', 'kode_cabang' => $cabang, 'status_aktif' => Karyawan::STATUS_AKTIF,
            ]);
            Lembur::create(['nik' => $nik, 'tanggal_lembur' => '2026-09-01', 'pekerjaan' => 'x', 'tempat' => 'x']);
        }
    }

    private function user(string $role, ?string $cabang): User
    {
        $user = User::factory()->create([
            'name' => $role, 'email' => str_replace(' ', '', $role).'@test.id', 'kode_cabang' => $cabang]);

        return $user->assignRole($role);
    }

    public function test_admin_cabang_hanya_melihat_data_cabangnya(): void
    {
        $admin = $this->user('admin cabang', 'CBG1');

        $this->assertSame('CBG1', $admin->scopedCabang());
        $this->assertSame(['1001'], Lembur::visibleTo($admin)->pluck('nik')->all());
        $this->assertSame(['1001'], Karyawan::visibleTo($admin)->pluck('nik')->all());
    }

    public function test_hrd_dengan_kode_cabang_tetap_melihat_semua_cabang(): void
    {
        $hrd = $this->user('hrd', 'CBG1');

        $this->assertNull($hrd->scopedCabang());
        $this->assertCount(2, Lembur::visibleTo($hrd)->get());
    }

    public function test_admin_cabang_wajib_punya_cabang_saat_dibuat(): void
    {
        Permission::create(['name' => 'users-create-admin', 'guard_name' => 'user']);
        Permission::create(['name' => 'dashboard-view-admin', 'guard_name' => 'user']);
        $superAdmin = $this->user('hrd', null);
        $superAdmin->givePermissionTo(['users-create-admin', 'dashboard-view-admin']);

        DB::table('departemen')->insert(['kode_dept' => 'OPS', 'nama_dept' => 'Operasional']);
        $jabatan = Jabatan::create(['nama_jabatan' => 'Admin Cabang', 'role_id' => Role::findByName('admin cabang', 'user')->id]);

        $this->actingAs($superAdmin, 'user')
            ->post('/users/store', [
                'name' => 'Baru', 'email' => 'baru@test.id', ...$this->kredensialBaru(),
                'kode_dept' => 'OPS', 'jabatan_id' => $jabatan->id, 'kode_cabang' => '',
            ])
            ->assertSessionHasErrors('kode_cabang');

        $this->assertDatabaseMissing('users', ['email' => 'baru@test.id']);
    }
}
