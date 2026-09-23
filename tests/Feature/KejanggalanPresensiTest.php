<?php

namespace Tests\Feature;

use App\Models\Karyawan;
use App\Models\Presensi;
use App\Services\PresensiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KejanggalanPresensiTest extends TestCase
{
    use RefreshDatabase;

    private function cek(string $lokasi, mixed $akurasi): array
    {
        return app(PresensiService::class)->kejanggalanLokasi('1001', $lokasi, $akurasi);
    }

    public function test_lokasi_gps_asli_dianggap_wajar(): void
    {
        $this->assertSame([], $this->cek('-6.2146823,106.8451305', 12.5));
    }

    public function test_akurasi_hilang_nol_atau_buruk_ditandai(): void
    {
        $this->assertContains('akurasi GPS tidak terkirim', $this->cek('-6.2146823,106.8451305', null));
        $this->assertContains('akurasi GPS 0 m (tidak wajar)', $this->cek('-6.2146823,106.8451305', '0'));
        $this->assertContains('akurasi GPS rendah (±850 m)', $this->cek('-6.2146823,106.8451305', 850));
    }

    public function test_koordinat_bulat_ditandai(): void
    {
        $this->assertContains('koordinat terlalu bulat', $this->cek('-6.2146,106.8451305', 10));
        $this->assertContains('koordinat terlalu bulat', $this->cek('-6,106', 10));
    }

    public function test_koordinat_persis_sama_dengan_presensi_lalu_ditandai(): void
    {
        Karyawan::create([
            'nik' => '1001', 'nama_lengkap' => 'K', 'nama_panggilan' => 'K', 'no_hp' => '0',
            'password' => 'x', 'status_aktif' => Karyawan::STATUS_AKTIF,
        ]);
        Presensi::create(['nik' => '1001', 'tgl_presensi' => '2026-09-01', 'jam_in' => '08:00:00', 'foto_in' => 'a.png', 'lokasi_in' => '-6.2146823,106.8451305', 'status' => 'h']);

        $this->assertSame(['koordinat persis sama dengan presensi sebelumnya'], $this->cek('-6.2146823,106.8451305', 10));
    }
}
