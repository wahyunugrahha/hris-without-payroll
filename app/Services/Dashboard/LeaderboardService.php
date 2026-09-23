<?php

namespace App\Services\Dashboard;

use App\Models\Cabang;
use App\Models\KpiLeaderboardSnapshot;
use App\Models\LeaderboardSnapshot;
use App\Models\Presensi;
use App\Models\RekapBulanan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Leaderboard presensi & KPI per cabang untuk dashboard overview/TV.
 */
class LeaderboardService
{
    /** Kode cabang site tambang (setting "cabang_tambang"). */
    private array $siteBranches;

    public function __construct()
    {
        $this->siteBranches = array_map('trim', explode(',', get_setting('cabang_tambang', 'CBNG0003,CBNG0011,RBJ,TBKR,CBNG0002')));
    }

    public function siteBranches(): array
    {
        return $this->siteBranches;
    }

    /**
     * Logic: Leaderboard per Cabang (OPTIMIZED - Mengatasi N+1 Query)
     */
    public function getBranchLeaderboards($hariini, $isMonthly = false, $startDate = null, $endDate = null, $limit = 5)
    {
        $cabangs = Cabang::orderBy('nama_cabang')->get();
        // Pluck semua kode cabang untuk query filter
        $kodeCabangs = $cabangs->pluck('kode_cabang')->toArray();

        $groupedData = collect([]);

        if (! $isMonthly) {
            // --- HARIAN (Presensi) ---
            $allData = DB::table('presensi')
                ->join('karyawan', 'presensi.nik', '=', 'karyawan.nik')
                ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
                ->select('presensi.jam_in', 'presensi.jam_out', 'karyawan.nama_lengkap', 'karyawan.foto', 'jabatan.nama_jabatan as jabatan_nama', 'karyawan.kode_cabang')
                ->where('presensi.tgl_presensi', $hariini)
                ->where('presensi.status', 'h')->where('presensi.jam_in', '!=', '00:00:00')
                ->whereIn('karyawan.kode_cabang', $kodeCabangs) // Filter sekaligus
                ->orderBy('presensi.jam_in', 'asc')
                ->get();

            // Grouping by kode_cabang via PHP Collection
            $groupedData = $allData->groupBy('kode_cabang');
        } else {
            // --- BULANAN (Points) ---
            $dateObj = Carbon::parse($endDate);
            $bulan = $dateObj->month;
            $tahun = $dateObj->year;

            $hasRekap = RekapBulanan::where('bulan', $bulan)->where('tahun', $tahun)->exists();

            if ($hasRekap) {
                $allData = RekapBulanan::query()
                    ->select(
                        'rekap_bulanans.nik',
                        'karyawan.nama_lengkap',
                        'rekap_bulanans.total_poin as total_points',
                        'karyawan.foto',
                        'jabatan.nama_jabatan as jabatan_nama',
                        'rekap_bulanans.kode_cabang'
                    )
                    ->join('karyawan', 'rekap_bulanans.nik', '=', 'karyawan.nik')
                    ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
                    ->where('rekap_bulanans.bulan', $bulan)
                    ->where('rekap_bulanans.tahun', $tahun)
                    ->whereIn('rekap_bulanans.kode_cabang', $kodeCabangs)
                    ->orderByDesc('rekap_bulanans.total_poin')
                    ->orderBy('rekap_bulanans.avg_jam_masuk', 'asc')
                    ->get();
            } else {
                $rawCollection = LeaderboardSnapshot::query()
                    ->select('leaderboard_snapshots.nik', 'leaderboard_snapshots.nama_lengkap', DB::raw('SUM(leaderboard_snapshots.points) as total_points'), 'karyawan.foto', 'jabatan.nama_jabatan as jabatan_nama', 'leaderboard_snapshots.kode_cabang')
                    ->join('karyawan', 'leaderboard_snapshots.nik', '=', 'karyawan.nik')
                    ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
                    ->whereBetween('leaderboard_snapshots.date', [$startDate, $endDate])
                    ->whereIn('leaderboard_snapshots.kode_cabang', $kodeCabangs)
                    ->groupBy('leaderboard_snapshots.nik', 'leaderboard_snapshots.nama_lengkap', 'karyawan.foto', 'jabatan.nama_jabatan', 'leaderboard_snapshots.kode_cabang')
                    ->get();

                $allData = $this->applyLiveTieBreaker($rawCollection, $startDate, $endDate, 'total_points');
            }

            $groupedData = $allData->groupBy('kode_cabang');
        }

        // Susun hasil akhir sesuai urutan cabang
        $results = [];
        foreach ($cabangs as $cabang) {
            // Ambil data dari hasil grouping, jika tidak ada return empty collection
            // Gunakan take(5) untuk membatasi
            $rows = isset($groupedData[$cabang->kode_cabang])
                ? ($limit ? $groupedData[$cabang->kode_cabang]->take($limit) : $groupedData[$cabang->kode_cabang])
                : collect([]);

            $results[$cabang->kode_cabang] = [
                'cabang' => $cabang,
                'rows' => $rows,
            ];
        }

        return $results;
    }

    /**
     * Logic: KPI Leaderboard per Cabang (Terpisah dari Presensi)
     */
    public function getKpiBranchLeaderboards($hariini, $isMonthly = false, $startDate = null, $endDate = null, $limit = 5)
    {
        $cabangs = Cabang::orderBy('nama_cabang')->get();
        $kodeCabangs = $cabangs->pluck('kode_cabang')->toArray();

        if (! $isMonthly) {
            // --- HARIAN KPI ---
            $allData = KpiLeaderboardSnapshot::query()
                ->select('kpi_leaderboard_snapshots.nik', 'kpi_leaderboard_snapshots.nama_lengkap', DB::raw('SUM(kpi_leaderboard_snapshots.points) as total_points'), 'karyawan.foto', 'jabatan.nama_jabatan as jabatan_nama', 'kpi_leaderboard_snapshots.kode_cabang')
                ->join('karyawan', 'kpi_leaderboard_snapshots.nik', '=', 'karyawan.nik')
                ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
                ->whereIn('kpi_leaderboard_snapshots.kode_cabang', $kodeCabangs)
                ->where('kpi_leaderboard_snapshots.date', $hariini)
                ->groupBy('kpi_leaderboard_snapshots.nik', 'kpi_leaderboard_snapshots.nama_lengkap', 'karyawan.foto', 'jabatan.nama_jabatan', 'kpi_leaderboard_snapshots.kode_cabang')
                ->orderByDesc('total_points')
                ->get();
        } else {
            // --- BULANAN KPI ---
            $dateObj = Carbon::parse($endDate);
            $bulan = $dateObj->month;
            $tahun = $dateObj->year;

            $hasRekap = RekapBulanan::where('bulan', $bulan)->where('tahun', $tahun)->exists();

            if ($hasRekap) {
                $allData = RekapBulanan::query()
                    ->select(
                        'rekap_bulanans.nik',
                        'karyawan.nama_lengkap',
                        'rekap_bulanans.total_poin_kpi as total_points',
                        'karyawan.foto',
                        'jabatan.nama_jabatan as jabatan_nama',
                        'rekap_bulanans.kode_cabang'
                    )
                    ->join('karyawan', 'rekap_bulanans.nik', '=', 'karyawan.nik')
                    ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
                    ->where('rekap_bulanans.bulan', $bulan)
                    ->where('rekap_bulanans.tahun', $tahun)
                    ->whereIn('rekap_bulanans.kode_cabang', $kodeCabangs)
                    ->orderByDesc('rekap_bulanans.total_poin_kpi')
                    ->get();
            } else {
                $allData = KpiLeaderboardSnapshot::query()
                    ->select('kpi_leaderboard_snapshots.nik', 'kpi_leaderboard_snapshots.nama_lengkap', DB::raw('SUM(kpi_leaderboard_snapshots.points) as total_points'), 'karyawan.foto', 'jabatan.nama_jabatan as jabatan_nama', 'kpi_leaderboard_snapshots.kode_cabang')
                    ->join('karyawan', 'kpi_leaderboard_snapshots.nik', '=', 'karyawan.nik')
                    ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
                    ->whereIn('kpi_leaderboard_snapshots.kode_cabang', $kodeCabangs)
                    ->whereBetween('kpi_leaderboard_snapshots.date', [$startDate, $endDate])
                    ->groupBy('kpi_leaderboard_snapshots.nik', 'kpi_leaderboard_snapshots.nama_lengkap', 'karyawan.foto', 'jabatan.nama_jabatan', 'kpi_leaderboard_snapshots.kode_cabang')
                    ->orderByDesc('total_points')
                    ->get();
            }
        }

        $groupedData = $allData->groupBy('kode_cabang');

        $results = [];
        foreach ($cabangs as $cabang) {
            $rows = isset($groupedData[$cabang->kode_cabang])
                ? ($limit ? $groupedData[$cabang->kode_cabang]->take($limit) : $groupedData[$cabang->kode_cabang])
                : collect([]);

            $results[$cabang->kode_cabang] = [
                'cabang' => $cabang,
                'rows' => $rows,
            ];
        }

        return $results;
    }

    /**
     * Helper to calculate and apply average clock-in tie-breaker on live leaderboard collections
     */
    public function applyLiveTieBreaker($collection, $startDate, $endDate, $pointsField = 'total_points')
    {
        $nikList = $collection->pluck('nik')->unique()->toArray();
        $presensiKaryawan = DB::table('presensi')
            ->whereBetween('tgl_presensi', [$startDate, $endDate])
            ->whereIn('nik', $nikList)
            ->where('status', 'h')
            ->whereNotNull('jam_in')
            ->where('jam_in', '!=', '00:00:00')
            ->select('nik', 'jam_in')
            ->get()
            ->groupBy('nik');

        foreach ($collection as $pk) {
            $presensis = $presensiKaryawan->get($pk->nik, collect());
            $totalSeconds = 0;
            $count = 0;
            foreach ($presensis as $p) {
                if (! empty($p->jam_in) && $p->jam_in !== '00:00:00') {
                    $parts = explode(':', $p->jam_in);
                    if (count($parts) >= 2) {
                        $hours = (int) $parts[0];
                        $minutes = (int) $parts[1];
                        $seconds = isset($parts[2]) ? (int) $parts[2] : 0;
                        $totalSeconds += ($hours * 3600) + ($minutes * 60) + $seconds;
                        $count++;
                    }
                }
            }
            $pk->avg_jam_masuk = $count > 0 ? round($totalSeconds / $count) : 999999;
        }

        return $collection->sortBy([
            [$pointsField, 'desc'],
            ['avg_jam_masuk', 'asc'],
        ])->values();
    }
}
