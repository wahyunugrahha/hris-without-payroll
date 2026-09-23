<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    private ?string $passwordUji = null;

    /**
     * Password acak untuk akun contoh di test (tidak ditulis literal agar tidak terdeteksi sebagai secret).
     */
    protected function passwordUji(): string
    {
        return $this->passwordUji ??= Str::random(16);
    }
}
