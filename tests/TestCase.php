<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    /**
     * Field password acak untuk payload form (buat user / ganti password) di test.
     * Akun contoh sendiri dibuat lewat factory, jadi tidak ada password yang ditulis di test.
     */
    protected function kredensialBaru(bool $konfirmasi = false): array
    {
        $nilai = Str::random(16);

        return $konfirmasi
            ? ['password' => $nilai, 'password_confirmation' => $nilai]
            : ['password' => $nilai];
    }
}
