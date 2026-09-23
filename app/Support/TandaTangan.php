<?php

namespace App\Support;

use Illuminate\Support\Facades\URL;

/**
 * QR tanda tangan digital pada dokumen cetak. QR berisi signed URL ke /ttd, sehingga teks
 * tanda tangan tidak bisa diubah/dipalsukan tanpa membuat tanda tangan URL tidak valid.
 */
final class TandaTangan
{
    public static function url(string $teks): string
    {
        // Tanda tangan relatif (path + query saja) agar tetap valid di balik proxy / beda domain & skema.
        return url(URL::signedRoute('ttd.view', ['text' => $teks], absolute: false));
    }

    /**
     * URL gambar QR (dibuat oleh api.qrserver.com) yang mengarah ke url().
     */
    public static function qrImage(string $teks, int $ukuran = 120): string
    {
        return "https://api.qrserver.com/v1/create-qr-code/?size={$ukuran}x{$ukuran}&data=".urlencode(self::url($teks));
    }
}
