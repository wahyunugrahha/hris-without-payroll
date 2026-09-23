<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KaryawanAdminTest extends TestCase
{
    use RefreshDatabase;

    private Jabatan $jabatan;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        DB::table('cabang')->insert([
            ['kode_cabang' => 'CBG1', 'nama_cabang' => 'Cabang 1', 'lokasi_kantor' => '0,0', 'radius' => 100],
            ['kode_cabang' => 'CBG2', 'nama_cabang' => 'Cabang 2', 'lokasi_kantor' => '0,0', 'radius' => 100],
        ]);
        DB::table('departemen')->insert(['kode_dept' => 'OPS', 'nama_dept' => 'Operasional']);
        $this->jabatan = Jabatan::create(['nama_jabatan' => 'Staff', 'role_id' => Role::create(['name' => 'staff', 'guard_name' => 'karyawan'])->id]);

        foreach (['dashboard-view-admin', 'karyawan-create-admin', 'karyawan-edit-admin'] as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'user']);
        }
        Role::create(['name' => 'admin cabang', 'guard_name' => 'user']);
    }

    private function admin(?string $cabang = null): User
    {
        $user = User::create(['name' => 'Admin', 'email' => uniqid().'@test.id', 'password' => Hash::make($this->passwordUji()), 'kode_cabang' => $cabang]);
        $user->givePermissionTo(['dashboard-view-admin', 'karyawan-create-admin', 'karyawan-edit-admin']);

        return $cabang ? $user->assignRole('admin cabang') : $user;
    }

    private function form(array $override = []): array
    {
        return $override + [
            'nik' => '1001', 'nama_lengkap' => 'Budi Santoso', 'nama_panggilan' => 'Budi', 'no_hp' => '0812',
            'jabatan_id' => $this->jabatan->id, 'kode_dept' => 'OPS', 'kode_cabang' => 'CBG1',
        ];
    }

    public function test_tambah_karyawan_hanya_menyimpan_field_form(): void
    {
        $this->actingAs($this->admin(), 'user')
            ->post('/karyawan/store', $this->form([
                'foto' => UploadedFile::fake()->image('a.jpg'),
                'status_aktif' => Karyawan::STATUS_DIBERHENTIKAN, // bukan field form tambah
                'remember_token' => 'dipalsukan',
            ]))
            ->assertSessionHas('success');

        $karyawan = Karyawan::find('1001');
        $this->assertSame(Karyawan::STATUS_AKTIF, $karyawan->status_aktif);
        $this->assertNull($karyawan->remember_token);
        $this->assertTrue(Hash::check('123456', $karyawan->password));
        Storage::disk('public')->assertExists('uploads/karyawan/'.$karyawan->foto);
        $this->assertTrue($karyawan->hasRole('staff'));
    }

    public function test_cabang_harus_valid(): void
    {
        $this->actingAs($this->admin(), 'user')
            ->post('/karyawan/store', $this->form(['kode_cabang' => 'TIDAKADA']))
            ->assertSessionHasErrors('kode_cabang');
    }

    public function test_ganti_nik_ikut_memindahkan_foto(): void
    {
        Storage::disk('public')->put('uploads/karyawan/1001_lama.jpg', 'x');
        Karyawan::create($this->form(['password' => 'x', 'status_aktif' => Karyawan::STATUS_AKTIF, 'foto' => '1001_lama.jpg']));

        $this->actingAs($this->admin(), 'user')
            ->put('/karyawan/1001/update', $this->form(['nik' => '2002']))
            ->assertSessionHas('success');

        $foto = Karyawan::find('2002')->foto;
        $this->assertStringStartsWith('2002_', $foto);
        Storage::disk('public')->assertExists('uploads/karyawan/'.$foto);
        Storage::disk('public')->assertMissing('uploads/karyawan/1001_lama.jpg');
    }

    public function test_admin_cabang_tidak_bisa_mengubah_karyawan_cabang_lain(): void
    {
        Karyawan::create($this->form(['password' => 'x', 'status_aktif' => Karyawan::STATUS_AKTIF]));

        $this->actingAs($this->admin('CBG2'), 'user')
            ->put('/karyawan/1001/update', $this->form(['nama_lengkap' => 'Diubah']))
            ->assertSessionHas('warning');

        $this->assertSame('Budi Santoso', Karyawan::find('1001')->nama_lengkap);
    }

    public function test_perpanjang_kontrak_mengaktifkan_kembali_karyawan_nonaktif(): void
    {
        Karyawan::create($this->form([
            'password' => 'x', 'status_aktif' => Karyawan::STATUS_NONAKTIF,
            'tanggal_habis_kontrak' => now()->subMonth()->toDateString(), 'tanggal_keluar' => now()->subMonth()->toDateString(),
        ]));

        $this->actingAs($this->admin(), 'user')
            ->put('/karyawan/1001/update', $this->form([
                'status_aktif' => Karyawan::STATUS_NONAKTIF,
                'tanggal_keluar' => now()->subMonth()->toDateString(),
                'tanggal_habis_kontrak' => now()->addYear()->toDateString(),
            ]))
            ->assertSessionHas('success');

        $karyawan = Karyawan::find('1001');
        $this->assertSame(Karyawan::STATUS_AKTIF, $karyawan->status_aktif);
        $this->assertNull($karyawan->tanggal_keluar);
    }
}
