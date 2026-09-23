<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\KPIDaily;
use App\Models\KPIMaster;
use App\Models\KPIMasterDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KpiKaryawanTest extends TestCase
{
    use RefreshDatabase;

    private Karyawan $karyawan;

    private Jabatan $jabatanOperator;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('cabang')->insert(['kode_cabang' => 'CBG1', 'nama_cabang' => 'Cabang 1', 'lokasi_kantor' => '0,0', 'radius' => 100]);
        DB::table('departemen')->insert(['kode_dept' => 'OPS', 'nama_dept' => 'Operasional']);
        $role = Role::create(['name' => 'staff', 'guard_name' => 'karyawan']);
        $this->jabatanOperator = Jabatan::create(['nama_jabatan' => 'Operator', 'role_id' => $role->id]);
        $jabatanStaff = Jabatan::create(['nama_jabatan' => 'Staff Umum', 'role_id' => $role->id]);

        KPIMaster::create(['kode_master' => 'KPI-OPR', 'nama_kpi' => 'Operator', 'jabatan_id' => $this->jabatanOperator->id, 'kode_dept' => 'OPS', 'kode_cabang' => 'CBG1', 'is_active' => true]);
        KPIMasterDetail::create(['kode_master' => 'KPI-OPR', 'indikator' => 'Cek unit', 'score_indikator' => 5, 'target' => '1', 'is_active' => true]);
        // Master staff dengan skor besar: fallback untuk jabatan tanpa master, tidak boleh dipakai operator.
        KPIMaster::create(['kode_master' => 'KPI-STF', 'nama_kpi' => 'Staff', 'jabatan_id' => $jabatanStaff->id, 'kode_dept' => 'OPS', 'kode_cabang' => 'CBG1', 'is_active' => true]);
        KPIMasterDetail::create(['kode_master' => 'KPI-STF', 'indikator' => 'Laporan', 'score_indikator' => 50, 'target' => '1', 'is_active' => true]);

        $this->karyawan = Karyawan::create([
            'nik' => '1001', 'nama_lengkap' => 'A', 'nama_panggilan' => 'A', 'no_hp' => '0', 'password' => 'x',
            'kode_cabang' => 'CBG1', 'kode_dept' => 'OPS', 'jabatan_id' => $this->jabatanOperator->id,
            'status_aktif' => Karyawan::STATUS_AKTIF, 'is_whitelist' => 1,
        ]);
        $this->actingAs($this->karyawan, 'karyawan');
    }

    public function test_master_paling_spesifik_didahulukan_sebelum_fallback_staff(): void
    {
        $this->assertSame('KPI-OPR', KPIMaster::untukKaryawan($this->karyawan)->kode_master);

        $tanpaMaster = new Karyawan(['kode_dept' => 'OPS', 'kode_cabang' => 'CBG1', 'jabatan_id' => 999]);
        $this->assertSame('KPI-STF', KPIMaster::untukKaryawan($tanpaMaster)->kode_master);
    }

    public function test_skor_diambil_dari_master_milik_karyawan(): void
    {
        $indikator = KPIMasterDetail::where('kode_master', 'KPI-OPR')->value('id');

        $this->post('/kpi/storekpi', ['action_type' => 'submitted', 'kpi' => [$indikator => ['is_checked' => 1]]])
            ->assertSessionHas('success');

        $this->assertSame(5, (int) KPIDaily::sole()->kpiDailyDetail()->sum('score'));
    }

    public function test_indikator_dari_master_lain_ditolak(): void
    {
        $indikatorLain = KPIMasterDetail::where('kode_master', 'KPI-STF')->value('id');

        $this->post('/kpi/storekpi', ['action_type' => 'submitted', 'kpi' => [$indikatorLain => ['is_checked' => 1]]])
            ->assertSessionHas('error');

        $this->assertSame(0, KPIDaily::count());
    }
}
