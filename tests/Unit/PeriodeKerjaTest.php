<?php

namespace Tests\Unit;

use App\Support\PeriodeKerja;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PeriodeKerjaTest extends TestCase
{
    public static function tanggalProvider(): array
    {
        return [
            'sebelum cutoff' => ['2026-02-10', '2026-01-26', '2026-02-25', 2, 2026],
            'tepat tanggal 25' => ['2026-02-25', '2026-01-26', '2026-02-25', 2, 2026],
            'tepat tanggal 26' => ['2026-02-26', '2026-02-26', '2026-03-25', 3, 2026],
            'akhir Januari (dulu overflow ke Maret)' => ['2026-01-31', '2026-01-26', '2026-02-25', 2, 2026],
            'lintas tahun' => ['2026-12-28', '2026-12-26', '2027-01-25', 1, 2027],
            'awal Januari' => ['2026-01-05', '2025-12-26', '2026-01-25', 1, 2026],
        ];
    }

    #[DataProvider('tanggalProvider')]
    public function test_periode_dari_tanggal(string $tanggal, string $mulai, string $selesai, int $bulan, int $tahun): void
    {
        $periode = PeriodeKerja::dari($tanggal);

        $this->assertSame([$mulai, $selesai], $periode->range());
        $this->assertSame($bulan, $periode->bulanKe());
        $this->assertSame($tahun, $periode->tahun());
    }

    public function test_periode_dari_bulan_menerima_string_request(): void
    {
        $this->assertSame(['2025-12-26', '2026-01-25'], PeriodeKerja::bulan('01', '2026')->range());
        $this->assertSame(['2026-02-26', '2026-03-25'], PeriodeKerja::bulan(3, 2026)->range());
    }
}
