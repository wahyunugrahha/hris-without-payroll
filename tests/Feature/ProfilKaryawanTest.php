<?php

namespace Tests\Feature;

use App\Models\Karyawan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilKaryawanTest extends TestCase
{
    use RefreshDatabase;

    public function test_ganti_foto_profil_menyimpan_file_baru_dan_menghapus_yang_lama(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uploads/karyawan/1001_lama.jpg', 'x');
        $karyawan = Karyawan::create([
            'nik' => '1001', 'nama_lengkap' => 'A', 'nama_panggilan' => 'A', 'no_hp' => '0812', 'password' => 'x',
            'status_aktif' => Karyawan::STATUS_AKTIF, 'foto' => '1001_lama.jpg',
        ]);

        $this->actingAs($karyawan, 'karyawan')->post('/updateprofile', [
            'no_hp' => '0812', 'email' => 'a@test.id', 'nama_panggilan' => 'A', 'alamat' => 'Jl. A',
            'agama' => 'Islam', 'status_pernikahan' => 'Belum Menikah', 'pendidikan_terakhir' => 'SMA',
            'foto' => UploadedFile::fake()->image('baru.png'),
        ])->assertSessionHas('success');

        $foto = $karyawan->fresh()->foto;
        $this->assertMatchesRegularExpression('/^1001_\d+\.png$/', $foto);
        Storage::disk('public')->assertExists('uploads/karyawan/'.$foto);
        Storage::disk('public')->assertMissing('uploads/karyawan/1001_lama.jpg');
    }
}
