<?php

namespace Tests\Feature;

use App\Models\Izin;
use App\Models\Karyawan;
use App\Models\Presensi;
use App\Models\User;
use App\Support\CutiDatesMeta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class IzinApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('cabang')->insert(['kode_cabang' => 'CBG1', 'nama_cabang' => 'Cabang 1', 'lokasi_kantor' => '0,0', 'radius' => 100]);
        DB::table('master_cuti')->insert(['kode_cuti' => 'CTH', 'nama_cuti' => 'Cuti Tahunan', 'jml_hari' => 12]);
        // Jam kerja cadangan saat karyawan belum punya jadwal (dibuat JamKerjaSeeder di production).
        DB::table('jam_kerja')->insert(['kode_jam_kerja' => 'JK01', 'nama_jam_kerja' => 'Normal', 'awal_jam_masuk' => '06:00', 'jam_masuk' => '08:00', 'akhir_jam_masuk' => '09:00', 'jam_pulang' => '17:00']);
        Karyawan::create([
            'nik' => '1001', 'nama_lengkap' => 'A', 'nama_panggilan' => 'A', 'no_hp' => '0', 'password' => 'x',
            'kode_cabang' => 'CBG1', 'status_aktif' => Karyawan::STATUS_AKTIF,
        ]);

        foreach (['dashboard-view-admin', 'pengajuan-izin-approve-admin'] as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'user']);
        }
        $admin = User::create(['name' => 'HRD', 'email' => 'hrd@test.id', 'password' => Hash::make('rahasia123')]);
        $admin->givePermissionTo(['dashboard-view-admin', 'pengajuan-izin-approve-admin']);
        $this->actingAs($admin, 'user');
    }

    private function izin(string $status, array $tanggal, int $approved = 0): Izin
    {
        return Izin::create([
            'kode_izin' => 'IZ09260001', 'nik' => '1001', 'status' => $status, 'status_approved' => $approved,
            'kode_cuti' => $status === 'c' ? 'CTH' : null,
            'tgl_izin_dari' => min($tanggal), 'tgl_izin_sampai' => max($tanggal),
            'keterangan' => Izin::isMultiDateStatus($status) ? CutiDatesMeta::append('alasan', $tanggal) : 'alasan',
        ]);
    }

    private function presensi(string $tanggal, array $attrs = []): Presensi
    {
        return Presensi::create(array_merge([
            'nik' => '1001', 'tgl_presensi' => $tanggal, 'status' => 'h', 'jam_in' => '07:30:00',
            'foto_in' => 'in.png', 'lokasi_in' => '0,0',
        ], $attrs));
    }

    private function putuskan(int $status, array $extra = [])
    {
        return $this->post('/presensi/approveizinsakit', ['id_izinsakit_from' => 'IZ09260001', 'status_approved' => $status] + $extra);
    }

    public function test_setujui_izin_absen_membuat_presensi_untuk_setiap_tanggal(): void
    {
        $this->izin('i', ['2026-09-01', '2026-09-02']);

        $this->putuskan(1)->assertSessionHas('success');

        $this->assertSame(['i', 'i'], Presensi::orderBy('tgl_presensi')->pluck('status')->all());
        $this->assertNotNull(Izin::find('IZ09260001')->status_decided_at);
    }

    public function test_setujui_cuti_tidak_berurutan_tidak_menghapus_hadir_di_antaranya(): void
    {
        $this->izin('c', ['2026-09-01', '2026-09-03']);
        $this->presensi('2026-09-02');

        $this->putuskan(1)->assertSessionHas('success');

        $this->assertSame(
            ['2026-09-01' => 'c', '2026-09-02' => 'h', '2026-09-03' => 'c'],
            Presensi::orderBy('tgl_presensi')->get()->mapWithKeys(fn ($p) => [$p->tgl_presensi->toDateString() => $p->status])->all()
        );
    }

    public function test_setujui_sebagian_mempersempit_tanggal_pengajuan(): void
    {
        $this->izin('c', ['2026-09-01', '2026-09-02', '2026-09-03']);

        $this->putuskan(1, ['selected_cuti_dates' => '2026-09-02,2026-09-03,2026-09-09'])->assertSessionHas('success');

        $izin = Izin::find('IZ09260001');
        $this->assertSame(['2026-09-02', '2026-09-03'], $izin->tanggalDiajukan());
        $this->assertStringContainsString('[PARTIAL] Disetujui: 2 dari 3 hari.', $izin->keterangan);
        $this->assertSame(2, Presensi::where('status', 'c')->count());
    }

    public function test_tolak_izin_terlambat_pending_tidak_menghapus_absen_masuk(): void
    {
        $this->izin('t', ['2026-09-01']);
        $this->presensi('2026-09-01', ['jam_in' => '08:15:00']);

        $this->putuskan(2, ['catatan_ditolak' => 'Tanpa alasan'])->assertSessionHas('success');

        $this->assertSame('08:15:00', Presensi::sole()->jam_in);
        $this->assertSame('Tanpa alasan', Izin::find('IZ09260001')->catatan_ditolak);
    }

    public function test_batalkan_persetujuan_cuti_menghapus_presensi_cuti(): void
    {
        $this->izin('s', ['2026-09-01']);
        $this->putuskan(1);

        $this->post('/presensi/IZ09260001/batalkanizinsakit')->assertSessionHas('success');

        $this->assertSame(0, Presensi::count());
        $this->assertSame(0, (int) Izin::find('IZ09260001')->status_approved);
    }

    public function test_status_keputusan_tidak_valid_ditolak(): void
    {
        $this->izin('i', ['2026-09-01']);

        $this->putuskan(5)->assertSessionHasErrors('status_approved');

        $this->assertSame(0, (int) Izin::find('IZ09260001')->status_approved);
    }
}
