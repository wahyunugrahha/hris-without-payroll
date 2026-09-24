<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * Jumlah data per status untuk satu bulan, dipakai tab status (Semua / Menunggu / …)
 * di halaman persetujuan admin. Angkanya "reset" tiap bulan karena hanya menghitung
 * data dalam bulan tersebut.
 *
 * Panggil SEBELUM filter status diterapkan ke $query agar semua status terhitung.
 */
final class JumlahPerStatus
{
    /**
     * @param  string|null  $kolomTanggal  null bila $query sudah dibatasi ke bulan itu
     * @return array<string, int> status => jumlah (kunci string), ditambah 'semua'
     */
    public static function bulanan(Builder $query, string $kolomStatus, ?string $kolomTanggal, Carbon $bulan): array
    {
        $q = (clone $query)->reorder();
        if ($kolomTanggal) {
            $q->where($kolomTanggal, '>=', $bulan->copy()->startOfMonth()->toDateString())
                ->where($kolomTanggal, '<', $bulan->copy()->startOfMonth()->addMonth()->toDateString());
        }

        $base = $q instanceof EloquentBuilder ? $q->toBase() : $q;
        // Buang kolom select bawaan query daftar; cukup status + jumlah.
        $base->columns = null;
        $base->bindings['select'] = [];

        $jumlah = $base
            ->selectRaw($kolomStatus.' as status_kunci, count(*) as jumlah')
            ->groupBy($kolomStatus)
            ->pluck('jumlah', 'status_kunci')
            ->mapWithKeys(fn ($n, $status) => [(string) $status => (int) $n])
            ->all();

        return $jumlah + ['semua' => array_sum($jumlah)];
    }
}
