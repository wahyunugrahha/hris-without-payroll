<?php

namespace App\Support;

/**
 * Foto selfie absen dikirim kamera browser sebagai data URL base64 ("data:image/jpeg;base64,...").
 */
final class FotoBase64
{
    /**
     * Isi file gambar, atau null jika data kosong / bukan gambar yang valid.
     */
    public static function decode(?string $dataUrl): ?string
    {
        $base64 = str_contains((string) $dataUrl, ';base64,')
            ? explode(';base64,', (string) $dataUrl, 2)[1]
            : (string) $dataUrl;

        $isi = base64_decode($base64, true);

        return ($isi !== false && $isi !== '' && @getimagesizefromstring($isi) !== false) ? $isi : null;
    }
}
