<?php

namespace Tests\Unit;

use App\Support\FotoBase64;
use PHPUnit\Framework\TestCase;

class FotoBase64Test extends TestCase
{
    private const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    public function test_data_url_gambar_valid(): void
    {
        $this->assertNotNull(FotoBase64::decode('data:image/png;base64,'.self::PNG_1X1));
        $this->assertNotNull(FotoBase64::decode(self::PNG_1X1));
    }

    public function test_bukan_gambar_ditolak(): void
    {
        $this->assertNull(FotoBase64::decode('data:image/png;base64,'.base64_encode('<?php system($_GET[1]);')));
        $this->assertNull(FotoBase64::decode('data:image/png;base64,@@@bukan-base64'));
        $this->assertNull(FotoBase64::decode(null));
    }
}
