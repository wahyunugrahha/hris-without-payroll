<?php

namespace Tests\Feature;

use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SesiKedaluwarsaTest extends TestCase
{
    use RefreshDatabase;

    private function karyawan(): Karyawan
    {
        return Karyawan::create([
            'nik' => '7001',
            'nama_lengkap' => 'Karyawan Uji',
            'nama_panggilan' => 'Uji',
            'no_hp' => '0800000000',
            'password' => 'x',
            'status_aktif' => Karyawan::STATUS_AKTIF,
        ]);
    }

    public function test_logout_karyawan_tidak_mengakhiri_sesi_admin_di_browser_yang_sama(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'user')->actingAs($this->karyawan(), 'karyawan')
            ->withSession(['_token' => 'token-tab-admin']);

        $this->post('/proseslogout')->assertRedirect(route('login'));

        $this->assertAuthenticatedAs($admin, 'user');
        $this->assertGuest('karyawan');
        // Token CSRF tab admin yang masih terbuka tetap berlaku.
        $this->assertSame('token-tab-admin', session()->token());
    }

    public function test_logout_admin_tanpa_login_lain_menghapus_sesi(): void
    {
        $this->actingAs(User::factory()->create(), 'user')->withSession(['_token' => 'token-lama']);

        $this->get('/proseslogoutadmin')->assertRedirect(route('loginadmin'));

        $this->assertGuest('user');
        $this->assertNotSame('token-lama', session()->token());
    }

    public function test_token_kedaluwarsa_kembali_ke_form_dengan_pesan_bukan_halaman_419(): void
    {
        Route::middleware('web')->post('/uji-419', fn () => throw new HttpException(419, 'CSRF token mismatch.'));

        $this->from('/panel')->post('/uji-419', ['email' => 'a@b.c', 'password' => 'rahasia'])
            ->assertRedirect('/panel')
            ->assertSessionHas('warning')
            ->assertSessionHasInput('email', 'a@b.c')
            ->assertSessionMissing('_old_input.password');

        $this->postJson('/uji-419')->assertStatus(419)->assertJsonStructure(['message', 'token']);
    }

    public function test_endpoint_token_csrf_mengembalikan_token_sesi(): void
    {
        $this->get('/csrf-token')->assertOk()->assertJson(['token' => session()->token()]);
    }
}
