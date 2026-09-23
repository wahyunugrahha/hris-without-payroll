<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Pencegahan eskalasi hak akses lewat menu Users / Roles / Permissions.
 */
class ManajemenAksesTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSION_KELOLA = [
        'dashboard-view-admin', 'users-create-admin', 'users-edit-admin', 'users-delete-admin',
        'roles-edit-admin', 'roles-delete-admin', 'permissions-edit-admin', 'permissions-delete-admin',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::PERMISSION_KELOLA as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'user']);
        }
        foreach (['administrator', 'hrd', 'admin cabang'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'user'])->givePermissionTo(self::PERMISSION_KELOLA);
        }
        DB::table('departemen')->insert(['kode_dept' => 'HRD', 'nama_dept' => 'HRD']);
    }

    private function user(string $role): User
    {
        return User::factory()->create([
            'name' => $role, 'email' => str_replace(' ', '', $role).uniqid().'@test.id'])->assignRole($role);
    }

    public function test_hrd_tidak_bisa_mengubah_role_administrator(): void
    {
        $admin = Role::findByName('administrator', 'user');

        $this->actingAs($this->user('hrd'), 'user')
            ->put("/users/roles/{$admin->id}", ['name' => 'administrator', 'permissions' => []])
            ->assertForbidden();

        $this->assertTrue($admin->fresh()->hasPermissionTo('users-create-admin'));
    }

    public function test_role_sistem_tidak_bisa_diganti_nama_atau_dihapus(): void
    {
        $adminCabang = Role::findByName('admin cabang', 'user');
        $hrd = $this->user('hrd');

        $this->actingAs($hrd, 'user')->put("/users/roles/{$adminCabang->id}", ['name' => 'cabang'])->assertSessionHas('warning');
        $this->actingAs($hrd, 'user')->delete("/users/roles/{$adminCabang->id}")->assertSessionHas('warning');

        $this->assertSame('admin cabang', $adminCabang->fresh()->name);
    }

    public function test_hrd_tidak_bisa_memberi_jabatan_administrator(): void
    {
        $jabatanAdmin = Jabatan::create(['nama_jabatan' => 'Super Admin', 'role_id' => Role::findByName('administrator', 'user')->id]);

        $this->actingAs($this->user('hrd'), 'user')
            ->post('/users/store', [
                'name' => 'Baru', 'email' => 'baru@test.id', ...$this->kredensialBaru(),
                'kode_dept' => 'HRD', 'jabatan_id' => $jabatanAdmin->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'baru@test.id']);
    }

    public function test_administrator_tetap_bisa_membuat_administrator(): void
    {
        $jabatanAdmin = Jabatan::create(['nama_jabatan' => 'Super Admin', 'role_id' => Role::findByName('administrator', 'user')->id]);

        $this->actingAs($this->user('administrator'), 'user')
            ->post('/users/store', [
                'name' => 'Baru', 'email' => 'baru@test.id', ...$this->kredensialBaru(),
                'kode_dept' => 'HRD', 'jabatan_id' => $jabatanAdmin->id,
            ])
            ->assertSessionHas('success');

        $this->assertTrue(User::where('email', 'baru@test.id')->first()->hasRole('administrator'));
    }

    public function test_permission_yang_dipakai_route_tidak_bisa_dihapus(): void
    {
        $permission = Permission::findByName('users-edit-admin', 'user');

        $this->actingAs($this->user('administrator'), 'user')
            ->delete("/users/permissions/{$permission->id}")
            ->assertSessionHas('warning');

        $this->assertNotNull($permission->fresh());
    }
}
