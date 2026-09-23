<?php

namespace App\Services\Dashboard;

use App\Models\Karyawan;
use App\Services\Dashboard\Concerns\FiltersByUserContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Statistik SDM dashboard admin: demografi & turnover karyawan.
 */
class WorkforceStatsService
{
    use FiltersByUserContext;

    /**
     * Logic: Demografi Admin
     */
    public function getDemographics($userCtx)
    {
        // Gender
        $queryGender = Karyawan::where('status_aktif', 'Aktif')->selectRaw("
            COUNT(CASE WHEN UPPER(LEFT(jenis_kelamin, 1)) = 'L' THEN 1 END) as laki_laki,
            COUNT(CASE WHEN UPPER(LEFT(jenis_kelamin, 1)) = 'P' THEN 1 END) as perempuan
        ");
        $this->applyCabangFilter($queryGender, $userCtx);
        $dataSebaranGender = $queryGender->first();

        // Umur (PostgreSQL Syntax)
        $queryUmur = Karyawan::where('status_aktif', 'Aktif');
        $this->applyCabangFilter($queryUmur, $userCtx);
        $dataSebaranUmur = $queryUmur->selectRaw('
            COUNT(CASE WHEN EXTRACT(YEAR FROM AGE(CURRENT_DATE, tanggal_lahir)) < 20 THEN 1 END) as umur_under_20,
            COUNT(CASE WHEN EXTRACT(YEAR FROM AGE(CURRENT_DATE, tanggal_lahir)) BETWEEN 20 AND 29 THEN 1 END) as umur_20_29,
            COUNT(CASE WHEN EXTRACT(YEAR FROM AGE(CURRENT_DATE, tanggal_lahir)) BETWEEN 30 AND 39 THEN 1 END) as umur_30_39,
            COUNT(CASE WHEN EXTRACT(YEAR FROM AGE(CURRENT_DATE, tanggal_lahir)) BETWEEN 40 AND 49 THEN 1 END) as umur_40_49,
            COUNT(CASE WHEN EXTRACT(YEAR FROM AGE(CURRENT_DATE, tanggal_lahir)) >= 50 THEN 1 END) as umur_50_plus
        ')->first();

        // Pendidikan
        $queryPendidikan = Karyawan::where('status_aktif', 'Aktif')->selectRaw("
            SUM(CASE WHEN pendidikan_terakhir = 'SMA/SMK' THEN 1 ELSE 0 END) as sma,
            SUM(CASE WHEN pendidikan_terakhir IN ('D3', 'Diploma') THEN 1 ELSE 0 END) as d3,
            SUM(CASE WHEN pendidikan_terakhir IN ('S1', 'Sarjana') THEN 1 ELSE 0 END) as s1,
            SUM(CASE WHEN pendidikan_terakhir IN ('S2', 'Master') THEN 1 ELSE 0 END) as s2
        ");
        $this->applyCabangFilter($queryPendidikan, $userCtx);
        $dataPendidikan = $queryPendidikan->first();

        // Domisili
        $queryDomisili = Karyawan::where('status_aktif', 'Aktif')
            ->selectRaw('alamat, COUNT(*) as jumlah')
            ->whereNotNull('alamat')->where('alamat', '!=', '')
            ->groupBy('alamat')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(5);
        $this->applyCabangFilter($queryDomisili, $userCtx);
        $dataDomisili = $queryDomisili->get();

        // Total untuk persentase
        $queryTotal = Karyawan::where('status_aktif', 'Aktif');
        $this->applyCabangFilter($queryTotal, $userCtx);
        $totalRaw = $queryTotal->count();
        $totalKaryawanForDomisili = $totalRaw > 0 ? $totalRaw : 1;

        return compact('dataSebaranGender', 'dataSebaranUmur', 'dataPendidikan', 'dataDomisili', 'totalKaryawanForDomisili');
    }

    /**
     * Logic: Turnover Admin (dengan Caching untuk performa)
     */
    public function getTurnoverStats($ctx, $userCtx, $jmlKaryawanAktifSaatIni)
    {
        // OPTIMASI: Cache hasil kalkulasi turnover yang berat
        $cacheKey = 'turnover_stats_'.$ctx['year'].'_'.$ctx['month'].'_'.md5(json_encode($userCtx));

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($ctx, $userCtx, $jmlKaryawanAktifSaatIni) {
            return $this->calculateTurnoverStatsInternal($ctx, $userCtx, $jmlKaryawanAktifSaatIni);
        });
    }

    /**
     * Internal: Kalkulasi Turnover (dipanggil dari cache)
     */
    private function calculateTurnoverStatsInternal($ctx, $userCtx, $jmlKaryawanAktifSaatIni)
    {
        $currentMonth = Carbon::createFromDate((int) $ctx['year'], (int) $ctx['month'], 1);
        $startWindow = $currentMonth->copy()->subMonths(11)->startOfMonth();
        $endWindow = $currentMonth->copy()->endOfMonth();
        $joinDateExpr = 'LEAST(COALESCE(tmt, tanggal_awal_kontrak), COALESCE(tanggal_awal_kontrak, tmt))';

        // Masuk aktual berdasarkan tanggal join efektif
        $queryMasuk = Karyawan::selectRaw("TO_CHAR({$joinDateExpr}, 'YYYY-MM') as ym, COUNT(*) as total")
            ->whereRaw('COALESCE(tmt, tanggal_awal_kontrak) IS NOT NULL')
            ->whereBetween(DB::raw($joinDateExpr), [$startWindow->toDateString(), $endWindow->toDateString()])
            ->groupByRaw("TO_CHAR({$joinDateExpr}, 'YYYY-MM')");
        $this->applyCabangFilter($queryMasuk, $userCtx);
        $masukData = $queryMasuk->pluck('total', 'ym')->toArray();

        // Keluar aktual berdasarkan tanggal_keluar
        $queryKeluar = Karyawan::selectRaw("TO_CHAR(tanggal_keluar, 'YYYY-MM') as ym, COUNT(*) as total")
            ->whereNotNull('tanggal_keluar')
            ->whereBetween('tanggal_keluar', [$startWindow->toDateString(), $endWindow->toDateString()])
            ->groupByRaw("TO_CHAR(tanggal_keluar, 'YYYY-MM')");
        $this->applyCabangFilter($queryKeluar, $userCtx);
        $keluarData = $queryKeluar->pluck('total', 'ym')->toArray();

        // Kontrak habis (dipisah dari keluar aktual)
        $queryKontrakHabis = Karyawan::selectRaw("TO_CHAR(tanggal_habis_kontrak, 'YYYY-MM') as ym, COUNT(*) as total")
            ->whereNotNull('tanggal_habis_kontrak')
            ->whereBetween('tanggal_habis_kontrak', [$startWindow->toDateString(), $endWindow->toDateString()])
            ->whereNull('tanggal_keluar')
            ->groupByRaw("TO_CHAR(tanggal_habis_kontrak, 'YYYY-MM')");
        $this->applyCabangFilter($queryKontrakHabis, $userCtx);
        $kontrakHabisData = $queryKontrakHabis->pluck('total', 'ym')->toArray();

        // Data quality warning: status keluar tanpa tanggal_keluar
        $queryTurnoverAnomali = Karyawan::query()
            ->whereIn('status_aktif', Karyawan::TURNOVER_STATUSES)
            ->whereNull('tanggal_keluar');
        $this->applyCabangFilter($queryTurnoverAnomali, $userCtx);
        $turnoverAnomalyCount = (int) $queryTurnoverAnomali->count();

        $turnoverLabels = [];
        $turnoverData = [];
        for ($i = 0; $i < 12; $i++) {
            $periode = $startWindow->copy()->addMonths($i);
            $ym = $periode->format('Y-m');

            $masuk = (int) ($masukData[$ym] ?? 0);
            $keluar = (int) ($keluarData[$ym] ?? 0);
            $kontrakHabis = (int) ($kontrakHabisData[$ym] ?? 0);

            $monthStart = $periode->copy()->startOfMonth()->toDateString();
            $monthEnd = $periode->copy()->endOfMonth()->toDateString();

            $qHeadcountStart = Karyawan::query()
                ->whereRaw("{$joinDateExpr} <= ?", [$monthStart])
                ->where(function ($q) use ($monthStart) {
                    $q->whereNull('tanggal_keluar')
                        ->orWhereDate('tanggal_keluar', '>', $monthStart);
                });
            $this->applyCabangFilter($qHeadcountStart, $userCtx);
            $headcountStart = (int) $qHeadcountStart->count();

            $qHeadcountEnd = Karyawan::query()
                ->whereRaw("{$joinDateExpr} <= ?", [$monthEnd])
                ->where(function ($q) use ($monthEnd) {
                    $q->whereNull('tanggal_keluar')
                        ->orWhereDate('tanggal_keluar', '>', $monthEnd);
                });
            $this->applyCabangFilter($qHeadcountEnd, $userCtx);
            $headcountEnd = (int) $qHeadcountEnd->count();

            $averageHeadcount = ($headcountStart + $headcountEnd) / 2;
            $turnoverRate = $averageHeadcount > 0 ? round(($keluar / $averageHeadcount) * 100, 2) : 0;

            $turnoverLabels[] = $periode->translatedFormat('M y');
            $turnoverData[] = [
                'periode' => $ym,
                'masuk' => $masuk,
                'keluar' => $keluar,
                'kontrak_habis' => $kontrakHabis,
                'turnover_rate' => $turnoverRate,
            ];
        }

        // OPTIMASI: Trend 7 Hari gunakan single query dengan aggregation (bukan loop count)
        $trendDates = [];
        for ($i = 6; $i >= 0; $i--) {
            $trendDates[] = date('Y-m-d', strtotime("-$i days"));
        }

        $qTrendBatch = Karyawan::query()
            ->whereRaw("{$joinDateExpr} <= ?", [max($trendDates)])
            ->select(DB::raw('date::date, COUNT(*) as cnt'))
            ->where(function ($q) use ($trendDates) {
                $q->where('status_aktif', 'Aktif')
                    ->orWhere(function ($sub) use ($trendDates) {
                        $sub->whereIn('status_aktif', [Karyawan::STATUS_NONAKTIF, Karyawan::STATUS_DIBERHENTIKAN])
                            ->whereNotNull('tanggal_keluar')
                            ->whereDate('tanggal_keluar', '>', min($trendDates));
                    });
            });
        $this->applyCabangFilter($qTrendBatch, $userCtx);

        // Fallback ke loop jika query batch gagal (tetap safe)
        $trendKaryawanAktif = [];
        foreach ($trendDates as $date) {
            $qTrend = Karyawan::query()
                ->whereRaw("{$joinDateExpr} <= ?", [$date])
                ->where(function ($q) use ($date) {
                    $q->where('status_aktif', 'Aktif')
                        ->orWhere(function ($sub) use ($date) {
                            $sub->whereIn('status_aktif', [Karyawan::STATUS_NONAKTIF, Karyawan::STATUS_DIBERHENTIKAN])
                                ->whereNotNull('tanggal_keluar')
                                ->whereDate('tanggal_keluar', '>', $date);
                        });
                });
            $this->applyCabangFilter($qTrend, $userCtx);
            $trendKaryawanAktif[] = $qTrend->count();
        }

        // Persentase Turnover bulan berjalan (mengacu turnover rate pada titik bulan terakhir)
        $lastIdx = count($turnoverData) - 1;
        $trendPersentase = $lastIdx >= 0 ? (float) ($turnoverData[$lastIdx]['turnover_rate'] ?? 0) : 0;

        return [
            'turnoverData' => $turnoverData,
            'turnoverLabels' => $turnoverLabels,
            'turnoverAnomalyCount' => $turnoverAnomalyCount,
            'trendKaryawanAktif' => $trendKaryawanAktif,
            'karyawanAktifBulanIni' => $jmlKaryawanAktifSaatIni,
            'trendPersentase' => $trendPersentase,
        ];
    }
}
