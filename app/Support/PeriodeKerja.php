<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Periode kerja perusahaan: tanggal 26 bulan sebelumnya s/d tanggal 25 bulan berjalan.
 * "Bulan periode" = bulan tanggal akhirnya (periode 26 Jan - 25 Feb adalah periode Februari).
 */
final class PeriodeKerja
{
    public const TANGGAL_MULAI = 26;

    public const TANGGAL_AKHIR = 25;

    private function __construct(
        public readonly CarbonImmutable $mulai,
        public readonly CarbonImmutable $selesai,
    ) {}

    /**
     * Periode milik bulan & tahun tertentu, mis. bulan(2, 2026) = 26 Jan 2026 - 25 Feb 2026.
     */
    public static function bulan(int|string $bulan, int|string $tahun): self
    {
        $selesai = CarbonImmutable::create((int) $tahun, (int) $bulan, self::TANGGAL_AKHIR)->startOfDay();

        return new self($selesai->subMonthNoOverflow()->day(self::TANGGAL_MULAI), $selesai);
    }

    /**
     * Periode yang memuat tanggal tersebut (default: hari ini).
     */
    public static function dari(DateTimeInterface|string|null $tanggal = null): self
    {
        $tanggal = CarbonImmutable::parse($tanggal ?? 'today');

        // Tanggal 26 ke atas sudah masuk periode bulan berikutnya. startOfMonth dulu agar tidak overflow (31 Jan -> Feb).
        $bulanPeriode = $tanggal->day >= self::TANGGAL_MULAI
            ? $tanggal->startOfMonth()->addMonthNoOverflow()
            : $tanggal;

        return self::bulan($bulanPeriode->month, $bulanPeriode->year);
    }

    public function bulanKe(): int
    {
        return $this->selesai->month;
    }

    public function tahun(): int
    {
        return $this->selesai->year;
    }

    /**
     * @return array{0: string, 1: string} [Y-m-d mulai, Y-m-d selesai]
     */
    public function range(): array
    {
        return [$this->mulai->toDateString(), $this->selesai->toDateString()];
    }
}
