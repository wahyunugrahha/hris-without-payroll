<?php

namespace Tests\Feature;

use Tests\TestCase;

class HtmlCacheTest extends TestCase
{
    public function test_halaman_html_tidak_disimpan_browser(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_url_css_berisi_versi_sesuai_waktu_ubah_file(): void
    {
        $versi = filemtime(public_path('assets/css/style.css'));

        $this->assertStringEndsWith("assets/css/style.css?v={$versi}", asset_v('assets/css/style.css'));
        $this->assertStringContainsString("style.css?v={$versi}", $this->get('/')->getContent());
    }
}
