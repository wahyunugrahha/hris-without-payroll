<?php

namespace Tests\Unit;

use App\Services\JadwalKerjaService;
use App\Services\PresensiService;
use PHPUnit\Framework\TestCase;

class JarakLokasiTest extends TestCase
{
    public function test_jarak_titik_yang_sama_nol_bukan_nan(): void
    {
        $this->assertSame(0.0, PresensiService::jarakMeter(-3.7955, 102.2592, -3.7955, 102.2592));
    }

    public function test_jarak_satu_derajat_bujur_di_khatulistiwa(): void
    {
        $this->assertEqualsWithDelta(111195, PresensiService::jarakMeter(0, 0, 0, 1), 5);
    }

    public function test_dalam_radius_salah_satu_lokasi_walau_bukan_yang_terdekat(): void
    {
        $service = new PresensiService(new JadwalKerjaService);
        $lokasi = collect([
            ['lat' => 0.0, 'lon' => 0.0, 'radius' => 10, 'nama' => 'Pos kecil'],      // terdekat (~556 m), radius kecil
            ['lat' => 0.0, 'lon' => 0.02, 'radius' => 3000, 'nama' => 'Area tambang'], // ~1668 m, masih di dalam radius
        ]);

        $this->assertTrue($service->dalamRadius($lokasi, 0.0, 0.005));
        $this->assertFalse($service->dalamRadius($lokasi, 0.0, -0.05));
    }
}
