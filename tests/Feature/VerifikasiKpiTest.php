<?php

namespace Tests\Feature;

use App\Models\Karyawan;
use App\Models\KPIDaily;
use App\Models\KPIDailyExtra;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Verifikasi KPI oleh admin dijaga KPIDailyPolicy: role verifikator & batas cabang.
 */
class VerifikasiKpiTest extends TestCase
{
    use RefreshDatabase;

    private KPIDaily $kpiCabang1;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('cabang')->insert([
            ['kode_cabang' => 'CBG1', 'nama_cabang' => 'Cabang 1', 'lokasi_kantor' => '0,0', 'radius' => 100],
            ['kode_cabang' => 'CBG2', 'nama_cabang' => 'Cabang 2', 'lokasi_kantor' => '0,0', 'radius' => 100],
        ]);
        foreach (['1001' => 'CBG1', '2001' => 'CBG2'] as $nik => $cabang) {
            Karyawan::create([
                'nik' => $nik, 'nama_lengkap' => "K$nik", 'nama_panggilan' => "K$nik", 'no_hp' => '0',
                'password' => 'x', 'kode_cabang' => $cabang, 'status_aktif' => Karyawan::STATUS_AKTIF,
            ]);
        }
        $this->kpiCabang1 = KPIDaily::create(['nik' => '1001', 'tanggal' => '2026-09-01', 'status' => 'submitted']);
        KPIDaily::create(['nik' => '2001', 'tanggal' => '2026-09-01', 'status' => 'submitted']);

        foreach (['dashboard-view-admin', 'kpi-view-admin', 'kpi-edit-admin'] as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'user']);
        }
        foreach (['hrd', 'owner', 'admin cabang'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'user']);
        }
    }

    private function user(string $role, ?string $cabang = null): User
    {
        $user = User::factory()->create([
            'name' => $role, 'email' => str_replace(' ', '', $role).'@test.id', 'kode_cabang' => $cabang]);

        return $user->assignRole($role)->givePermissionTo(['dashboard-view-admin', 'kpi-view-admin', 'kpi-edit-admin']);
    }

    public function test_hrd_bisa_approve_kpi(): void
    {
        $this->actingAs($this->user('hrd'), 'user')
            ->put("/kpi/indikatorkpi/{$this->kpiCabang1->id}/approve", ['action' => 'approve_hr'])
            ->assertSessionHas('success');

        $this->assertSame('approved_by_hr', $this->kpiCabang1->fresh()->status);
    }

    public function test_owner_hanya_bisa_melihat_tidak_bisa_menolak(): void
    {
        $this->actingAs($this->user('owner'), 'user')
            ->put("/kpi/indikatorkpi/{$this->kpiCabang1->id}/reject", ['alasan_reject' => 'x'])
            ->assertForbidden();

        $this->assertSame('submitted', $this->kpiCabang1->fresh()->status);
    }

    public function test_admin_cabang_tidak_bisa_menyetujui_kpi_cabang_lain(): void
    {
        $admin = $this->user('admin cabang', 'CBG2');

        $this->actingAs($admin, 'user')
            ->put("/kpi/indikatorkpi/{$this->kpiCabang1->id}/approve", ['action' => 'approve_hr'])
            ->assertForbidden();
    }

    public function test_persetujuan_massal_admin_cabang_hanya_untuk_cabangnya(): void
    {
        $this->actingAs($this->user('admin cabang', 'CBG2'), 'user')
            ->post('/kpi/rekap/karyawan/bulk-approve', ['niks' => ['1001', '2001'], 'bulan' => 9, 'tahun' => 2026])
            ->assertSessionHas('success');

        $this->assertSame('submitted', $this->kpiCabang1->fresh()->status);
        $this->assertSame('approved_by_hr', KPIDaily::where('nik', '2001')->value('status'));
    }

    public function test_kpi_tambahan_tidak_bisa_diubah_setelah_disetujui_hr(): void
    {
        $this->kpiCabang1->update(['status' => 'approved_by_hr']);
        $extra = KPIDailyExtra::create(['kpi_daily_id' => $this->kpiCabang1->id, 'indikator_tambahan' => 'Asli', 'score' => 1]);

        $this->actingAs($this->user('hrd'), 'user')
            ->put("/kpi/indikatorkpi/extra/{$extra->id}/update", ['indikator_tambahan' => 'Diubah', 'score' => 5])
            ->assertForbidden();

        $this->assertSame('Asli', $extra->fresh()->indikator_tambahan);
    }
}
