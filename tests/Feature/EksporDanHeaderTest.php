<?php

namespace Tests\Feature;

use App\Exports\KaryawanExport;
use App\Models\Karyawan;
use Tests\TestCase;

class EksporDanHeaderTest extends TestCase
{
    public function test_ekspor_karyawan_menetralkan_formula_excel(): void
    {
        $karyawan = new Karyawan([
            'nik' => '1001', 'nama_lengkap' => '=HYPERLINK("http://jahat.test","klik")',
            'nama_panggilan' => 'Budi', 'no_hp' => '+6281234', 'alamat' => '@SUM(1+1)',
        ]);

        $baris = (new KaryawanExport)->map($karyawan);

        $this->assertSame('\'=HYPERLINK("http://jahat.test","klik")', $baris[1]);
        $this->assertSame('Budi', $baris[2]);
        $this->assertSame("'+6281234", $baris[6]);
        $this->assertContains("'@SUM(1+1)", $baris);
    }

    public function test_header_keamanan_terpasang(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
