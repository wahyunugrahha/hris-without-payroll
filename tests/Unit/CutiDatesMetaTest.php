<?php

namespace Tests\Unit;

use App\Support\CutiDatesMeta;
use PHPUnit\Framework\TestCase;

class CutiDatesMetaTest extends TestCase
{
    public function test_append_lalu_parse_dan_strip(): void
    {
        $keterangan = CutiDatesMeta::append('Acara keluarga', ['2026-09-03', '2026-09-01']);

        $this->assertSame('Acara keluarga [CUTI_DATES:2026-09-03,2026-09-01]', $keterangan);
        $this->assertSame(['2026-09-01', '2026-09-03'], CutiDatesMeta::parse($keterangan));
        $this->assertSame('Acara keluarga', CutiDatesMeta::strip($keterangan));
    }

    public function test_append_mengganti_penanda_lama(): void
    {
        $keterangan = CutiDatesMeta::append('Liburan [CUTI_DATES:2026-01-01]', ['2026-02-02']);

        $this->assertSame(['2026-02-02'], CutiDatesMeta::parse($keterangan));
    }

    public function test_parse_data_lama_tanpa_penanda(): void
    {
        $this->assertSame(['2026-09-01', '2026-09-02'], CutiDatesMeta::parse('cuti 2026-09-02 dan 2026-09-01'));
        $this->assertSame([], CutiDatesMeta::parse(null));
    }

    public function test_compact_text_menggabungkan_tanggal_berurutan(): void
    {
        $this->assertSame(
            '01-09-2026 s/d 03-09-2026, 05-09-2026',
            CutiDatesMeta::compactText(['2026-09-05', '2026-09-01', '2026-09-02', '2026-09-03'])
        );
    }

    public function test_dates_from_range(): void
    {
        $this->assertSame(['2026-08-31', '2026-09-01'], CutiDatesMeta::datesFromRange('2026-08-31', '2026-09-01'));
        $this->assertSame(['2026-09-01'], CutiDatesMeta::datesFromRange('2026-09-01', null));
        $this->assertSame([], CutiDatesMeta::datesFromRange(null, null));
    }
}
