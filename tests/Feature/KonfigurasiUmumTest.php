<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KonfigurasiUmumTest extends TestCase
{
    use RefreshDatabase;

    public function test_nilai_konfigurasi_divalidasi(): void
    {
        DB::table('konfigurasi_umums')->insert(['key' => 'toleransi_keterlambatan', 'value' => '10']);
        foreach (['dashboard-view-admin', 'konfigurasi-umum-edit-admin'] as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'user']);
        }
        $admin = User::create(['name' => 'Admin', 'email' => 'a@test.id', 'password' => Hash::make($this->passwordUji())])
            ->givePermissionTo(['dashboard-view-admin', 'konfigurasi-umum-edit-admin']);

        $this->actingAs($admin, 'user')
            ->post('/konfigurasi/umum/update', ['settings' => ['toleransi_keterlambatan' => 'sepuluh']])
            ->assertSessionHasErrors('settings.toleransi_keterlambatan');

        $this->actingAs($admin, 'user')
            ->post('/konfigurasi/umum/update', ['settings' => ['toleransi_keterlambatan' => '15']])
            ->assertSessionHas('success');

        $this->assertSame('15', DB::table('konfigurasi_umums')->where('key', 'toleransi_keterlambatan')->value('value'));
    }
}
