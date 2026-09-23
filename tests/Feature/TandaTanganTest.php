<?php

namespace Tests\Feature;

use App\Support\TandaTangan;
use Tests\TestCase;

class TandaTanganTest extends TestCase
{
    public function test_qr_dari_sistem_terverifikasi(): void
    {
        $url = TandaTangan::url('Disetujui oleh: Budi, Tanggal: 01-09-2026');

        $this->get($url)
            ->assertOk()
            ->assertSee('TANDA TANGAN TERVERIFIKASI')
            ->assertSee('Disetujui oleh: Budi');
    }

    public function test_teks_yang_diubah_tidak_terverifikasi(): void
    {
        $url = str_replace('Budi', 'Palsu', TandaTangan::url('Disetujui oleh: Budi'));

        $this->get($url)->assertOk()->assertSee('BELUM TERVERIFIKASI');
    }

    public function test_qr_lama_tanpa_tanda_tangan_tetap_terbaca(): void
    {
        $this->get('/ttd?text='.urlencode('Dokumen lama'))
            ->assertOk()
            ->assertSee('BELUM TERVERIFIKASI')
            ->assertSee('Dokumen lama');
    }
}
