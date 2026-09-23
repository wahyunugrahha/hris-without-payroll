<?php

namespace App\Support;

use DateInterval;
use DatePeriod;
use DateTime;
use Exception;
use Illuminate\Support\Collection;

/**
 * Daftar tanggal cuti/roster (bisa tidak berurutan) disimpan sebagai penanda di akhir kolom
 * `izin.keterangan`, mis. "Acara keluarga [CUTI_DATES:2026-09-01,2026-09-03]".
 *
 * ponytail: format lama yang menumpang di kolom teks; pindahkan ke tabel izin_tanggal jika
 * perlu query per tanggal. Kelas ini satu-satunya tempat yang boleh membaca/menulis formatnya.
 */
final class CutiDatesMeta
{
    private const PREFIX = '[CUTI_DATES:';

    /**
     * @return list<string> tanggal Y-m-d, urut & unik
     */
    public static function parse(?string $keterangan): array
    {
        if (empty($keterangan)) {
            return [];
        }

        $metaContent = null;
        if (preg_match('/\[CUTI_DATES:([^\]]*)\]?/i', $keterangan, $matches)) {
            $metaContent = $matches[1] ?? '';
        }

        // Fallback untuk data lama/format rusak: ambil semua tanggal ISO dari keterangan.
        if ($metaContent === null) {
            preg_match_all('/\d{4}-\d{2}-\d{2}/', $keterangan, $allDateMatches);
            $metaContent = implode(',', $allDateMatches[0] ?? []);
        }

        return collect(explode(',', (string) $metaContent))
            ->map(fn ($date) => trim($date))
            ->filter(fn ($date) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Keterangan tanpa penanda tanggal (untuk ditampilkan ke user).
     */
    public static function strip(?string $keterangan): string
    {
        if (empty($keterangan)) {
            return '';
        }

        $clean = preg_replace('/\s*\[CUTI_DATES:[^\]]*\]?\s*/i', ' ', $keterangan);
        $clean = preg_replace('/\s*\|\s*$/', '', (string) $clean);

        return trim(preg_replace('/\s{2,}/', ' ', (string) $clean));
    }

    /**
     * @param  Collection|array<string>  $dates
     */
    public static function append(string $keterangan, Collection|array $dates): string
    {
        $cleanKeterangan = self::strip($keterangan);
        $dates = $dates instanceof Collection ? $dates->all() : $dates;

        if (empty($dates)) {
            return $cleanKeterangan;
        }

        return trim($cleanKeterangan.' '.self::PREFIX.implode(',', $dates).']');
    }

    /**
     * Ringkas tanggal jadi segmen, mis. "01-09-2026 s/d 03-09-2026, 05-09-2026".
     */
    public static function compactText(array $isoDates): string
    {
        $dates = collect($isoDates)
            ->filter(fn ($date) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date))
            ->unique()
            ->sort()
            ->values()
            ->all();

        if (empty($dates)) {
            return '';
        }

        $segments = [];
        $segmentStart = $dates[0];
        $segmentEnd = $dates[0];

        for ($i = 1; $i < count($dates); $i++) {
            $current = $dates[$i];

            if ($current === date('Y-m-d', strtotime($segmentEnd.' +1 day'))) {
                $segmentEnd = $current;

                continue;
            }

            $segments[] = [$segmentStart, $segmentEnd];
            $segmentStart = $current;
            $segmentEnd = $current;
        }
        $segments[] = [$segmentStart, $segmentEnd];

        $format = fn ($date) => date('d-m-Y', strtotime($date));

        return collect($segments)
            ->map(fn ($segment) => $segment[0] === $segment[1]
                ? $format($segment[0])
                : $format($segment[0]).' s/d '.$format($segment[1]))
            ->implode(', ');
    }

    /**
     * @return list<string> semua tanggal Y-m-d dari $fromDate s/d $toDate (inklusif)
     */
    public static function datesFromRange($fromDate, $toDate): array
    {
        if (empty($fromDate)) {
            return [];
        }

        $result = [];
        try {
            $start = new DateTime($fromDate);
            $end = new DateTime($toDate ?: $fromDate);
            if ($end < $start) {
                $end = new DateTime($fromDate);
            }

            $period = new DatePeriod($start, new DateInterval('P1D'), (clone $end)->modify('+1 day'));
            foreach ($period as $date) {
                $result[] = $date->format('Y-m-d');
            }
        } catch (Exception) {
            return [];
        }

        return $result;
    }
}
