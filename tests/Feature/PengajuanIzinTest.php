<?php

namespace Tests\Feature;

use App\Models\Izin;
use App\Models\Karyawan;
use App\Support\CutiDatesMeta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Alur pengajuan izin karyawan setelah controller dipecah per jenis.
 */
class PengajuanIzinTest extends TestCase
{
    use RefreshDatabase;

    private Karyawan $karyawan;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('cabang')->insert(['kode_cabang' => 'CBG1', 'nama_cabang' => 'Cabang 1', 'lokasi_kantor' => '0,0', 'radius' => 100]);
        DB::table('departemen')->insert(['kode_dept' => 'OPS', 'nama_dept' => 'Operasional']);
        DB::table('master_cuti')->insert(['kode_cuti' => 'CTH', 'nama_cuti' => 'Cuti Tahunan', 'jml_hari' => 2]);

        foreach (['izin-view-karyawan', 'izin-create-karyawan', 'izin-edit-karyawan'] as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'karyawan']);
        }

        $this->karyawan = Karyawan::create([
            'nik' => '1001', 'nama_lengkap' => 'A', 'nama_panggilan' => 'A', 'no_hp' => '0', 'password' => 'x',
            'kode_cabang' => 'CBG1', 'kode_dept' => 'OPS', 'status_aktif' => Karyawan::STATUS_AKTIF,
        ]);
        $this->karyawan->givePermissionTo(['izin-view-karyawan', 'izin-create-karyawan', 'izin-edit-karyawan']);
        $this->actingAs($this->karyawan, 'karyawan');
    }

    public function test_simpan_izin_absen_dengan_kode_berurutan(): void
    {
        $this->post('/pengajuanizin/storeizinabsen', ['dari' => '2026-09-10', 'sampai' => '2026-09-11', 'keterangan' => 'Urusan keluarga'])
            ->assertSessionHas('success');
        $this->post('/pengajuanizin/storeizinabsen', ['dari' => '2026-09-15', 'sampai' => '2026-09-15', 'keterangan' => 'Lagi'])
            ->assertSessionHas('success');

        $this->assertSame(['IZ09260001', 'IZ09260002'], Izin::orderBy('kode_izin')->pluck('kode_izin')->all());
    }

    public function test_izin_absen_ditolak_jika_tanggal_selesai_sebelum_mulai(): void
    {
        $this->post('/pengajuanizin/storeizinabsen', ['dari' => '2026-09-10', 'sampai' => '2026-09-09', 'keterangan' => 'x'])
            ->assertSessionHasErrors('sampai');

        $this->assertSame(0, Izin::count());
    }

    public function test_cuti_menyimpan_tanggal_terpilih_dan_melewati_hari_libur(): void
    {
        DB::table('hari_libur')->insert(['tanggal_libur' => '2026-09-02', 'keterangan' => 'Libur']);

        $this->post('/pengajuanizin/storeizincuti', [
            'kode_cuti' => 'CTH',
            'selected_dates' => '2026-09-03,2026-09-01,2026-09-02',
            'keterangan' => 'Acara keluarga',
        ])->assertSessionHas('success');

        $izin = Izin::sole();
        $this->assertSame(['2026-09-01', '2026-09-03'], $izin->tanggalDiajukan());
        $this->assertSame('Acara keluarga', CutiDatesMeta::strip($izin->keterangan));
    }

    public function test_cuti_ditolak_jika_melebihi_sisa_jatah(): void
    {
        $this->post('/pengajuanizin/storeizincuti', [
            'kode_cuti' => 'CTH',
            'selected_dates' => '2026-09-01,2026-09-02,2026-09-03',
            'keterangan' => 'Kebanyakan',
        ])->assertSessionHasErrors('jmlhari');

        $this->assertSame(0, Izin::count());
    }

    public function test_cuti_wajib_memilih_jenis_dan_tanggal(): void
    {
        $this->post('/pengajuanizin/storeizincuti', ['selected_dates' => '', 'keterangan' => 'x'])
            ->assertSessionHasErrors(['kode_cuti', 'selected_dates']);
    }

    public function test_link_edit_diteruskan_ke_form_sesuai_jenis(): void
    {
        Izin::create([
            'kode_izin' => 'IZ09260001', 'nik' => '1001', 'status' => 'r', 'status_approved' => 0,
            'tgl_izin_dari' => '2026-09-01', 'tgl_izin_sampai' => '2026-09-01',
        ]);

        $this->get('/pengajuanizin/IZ09260001/edit')
            ->assertRedirect(route('pengajuanizin.editizinroster', 'IZ09260001'));
    }

    public function test_izin_yang_sudah_disetujui_tidak_bisa_diubah(): void
    {
        $izin = Izin::create([
            'kode_izin' => 'IZ09260001', 'nik' => '1001', 'status' => 'i', 'status_approved' => 1,
            'tgl_izin_dari' => '2026-09-01', 'tgl_izin_sampai' => '2026-09-01', 'keterangan' => 'asli',
        ]);

        $this->put('/pengajuanizin/IZ09260001/updateizinabsen', ['dari' => '2026-09-02', 'sampai' => '2026-09-02', 'keterangan' => 'ubah'])
            ->assertSessionHas('error');

        $this->assertSame('asli', $izin->fresh()->keterangan);
    }

    public function test_ubah_cuti_juga_melewati_hari_libur(): void
    {
        DB::table('hari_libur')->insert(['tanggal_libur' => '2026-09-02', 'keterangan' => 'Libur']);
        Izin::create([
            'kode_izin' => 'IZ09260001', 'nik' => '1001', 'status' => 'c', 'kode_cuti' => 'CTH', 'status_approved' => 0,
            'tgl_izin_dari' => '2026-09-01', 'tgl_izin_sampai' => '2026-09-01',
            'keterangan' => CutiDatesMeta::append('awal', ['2026-09-01']),
        ]);

        $this->put('/pengajuanizin/IZ09260001/updateizincuti', [
            'kode_cuti' => 'CTH',
            'selected_dates' => '2026-09-01,2026-09-02,2026-09-03',
            'keterangan' => 'revisi',
        ])->assertSessionHas('success');

        $this->assertSame(['2026-09-01', '2026-09-03'], Izin::find('IZ09260001')->tanggalDiajukan());
    }
}
