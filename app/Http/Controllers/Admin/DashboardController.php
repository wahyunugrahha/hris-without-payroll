<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BpjsRequest;
use App\Models\Cabang;
use App\Models\Departemen;
use App\Models\DinasLuar;
// Models
use App\Models\HariLibur;
use App\Models\Izin;
use App\Models\Karyawan;
use App\Models\KPIDaily;
use App\Models\KpiLeaderboardSnapshot;
use App\Models\LeaderboardSnapshot;
use App\Models\Lembur;
use App\Models\Pengumuman;
use App\Models\Presensi;
use App\Models\RegistrationToken;
use App\Models\RekapBulanan;
use App\Models\SalaryIncrease;
use App\Support\PeriodeKerja;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Konfigurasi Cabang Site secara dinamis
     */
    private $siteBranches;

    public function __construct()
    {
        $this->siteBranches = array_map('trim', explode(',', get_setting('cabang_tambang', 'CBNG0003,CBNG0011,RBJ,TBKR,CBNG0002')));
    }

    // =========================================================================
    // SECTION 1: DASHBOARD ADMIN (HR/Pusat)
    // =========================================================================

    public function dashboardadmin(Request $request)
    {
        // 1. Setup Context
        $ctx = [
            'today' => date('Y-m-d'),
            'year' => date('Y'),
            'month' => date('m'),
        ];

        // 2. Cek Role & Filter Cabang
        $user = Auth::guard('user')->user();
        $isAdminCabang = $user && method_exists($user, 'hasRole') && $user->hasRole('admin cabang');
        $filterQ = trim((string) $request->query('q', ''));
        $filterCabang = (string) $request->query('cabang', '');
        $filterDept = (string) $request->query('dept', '');
        $filterPeriode = (string) $request->query('periode', '');

        if ($isAdminCabang && ! empty($user->kode_cabang)) {
            $filterCabang = $user->kode_cabang;
        }

        $userCtx = [
            'isAdminCabang' => $isAdminCabang,
            'kodeCabang' => $isAdminCabang ? $user->kode_cabang : null,
            'filterQ' => $filterQ,
            'filterCabang' => $filterCabang,
            'filterDept' => $filterDept,
        ];

        // 3. Ambil Data Modular
        $statsRealtime = $this->getRealtimeStats($ctx, $userCtx);
        $realtimeTrend = $this->getRealtimeTrends($ctx, $userCtx);
        $kpiVisualStats = $this->getKpiVisualStats($ctx, $userCtx);
        $statsDemografi = $this->getDemographics($userCtx);
        $statsTurnover = $this->getTurnoverStats($ctx, $userCtx, $statsRealtime['jmlkaryawan']);
        $listsData = $this->getDataLists($ctx, $userCtx);
        $requestSummary = $this->getRequestSummaryStats($userCtx);
        $globalFilters = $this->getGlobalFilters($userCtx);
        $criticalNotifications = $this->getCriticalNotifications($ctx, $userCtx, $statsRealtime);
        $selectedMonth = $request->query('bulan');
        $cultureWidgets = $this->getCultureAndDailyWidgets($ctx, $userCtx, $selectedMonth);

        // 4. Gabungkan Data
        $viewData = array_merge(
            $statsRealtime,
            $realtimeTrend,
            $kpiVisualStats,
            $statsDemografi,
            $statsTurnover,
            $listsData,
            $requestSummary,
            $globalFilters,
            $criticalNotifications,
            $cultureWidgets
        );

        $viewData['filterQ'] = $filterQ;
        $viewData['filterCabang'] = $filterCabang;
        $viewData['filterDept'] = $filterDept;
        $viewData['filterPeriode'] = $filterPeriode;

        return view('admin.dashboard.dashboardadmin', $viewData);
    }

    // =========================================================================
    // SECTION 2: DASHBOARD OVERVIEW (Monitoring Umum)
    // =========================================================================

    public function dashboardoverview(Request $request)
    {
        // 1. Setup Periode & Tanggal
        $periodInfo = $this->getPeriodRange($request);
        $hariini = date('Y-m-d');

        // 2. Statistik Header (Presensi Hari Ini)
        $headerStats = $this->getDailyHeaderStats($hariini);

        // 3. List Data Operasional
        $lists = $this->getOverviewLists($hariini, $periodInfo['startDate'], $periodInfo['endDate']);

        // 3b. Activity Lists (Izin/Sakit/Cuti, Dinas Luar, Lembur, SP)
        $activityLists = $this->getOverviewActivityLists($hariini);

        // 4. Leaderboard per Cabang (OPTIMIZED: Eager Loading)
        // Kita gunakan $isMonthly = true dan false untuk mengambil dua jenis data
        $leaderboardsByCabang = $this->getBranchLeaderboards($hariini, false, $periodInfo['startDate'], $periodInfo['endDate'], null); // Harian
        $leaderboardsByCabangMonthly = $this->getBranchLeaderboards($hariini, true, $periodInfo['startDate'], $periodInfo['endDate'], null); // Bulanan

        // 5. KPI Leaderboard per Cabang
        $kpiLeaderboardsByCabang = $this->getKpiBranchLeaderboards($hariini, false, $periodInfo['startDate'], $periodInfo['endDate'], null); // Harian KPI
        $kpiLeaderboardsByCabangMonthly = $this->getKpiBranchLeaderboards($hariini, true, $periodInfo['startDate'], $periodInfo['endDate'], null); // Bulanan KPI

        return view('admin.dashboard.dashboardoverview', array_merge(
            $headerStats,
            $lists,
            $activityLists,
            compact(
                'leaderboardsByCabang',
                'leaderboardsByCabangMonthly',
                'kpiLeaderboardsByCabang',
                'kpiLeaderboardsByCabangMonthly'
            ),
            ['periodeLabel' => $periodInfo['label'], 'startDate' => $periodInfo['startDate'], 'endDate' => $periodInfo['endDate']],
            ['periodeOptions' => $this->getPeriodeOptions()]
        ));
    }

    // =========================================================================
    // SECTION 3: DASHBOARD TV (Monitoring Layar Besar)
    // =========================================================================

    public function dashboardtv(Request $request)
    {
        // 1. Setup Periode
        $periodInfo = $this->getPeriodRange($request);
        $debugDate = $request->query('date');
        $hariini = ($debugDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $debugDate))
            ? $debugDate
            : date('Y-m-d');

        // 2. Statistik Header
        $headerStats = $this->getDailyHeaderStats($hariini);

        // 3. Data Middle Grid (Top Performance & Activity)
        $tvStats = $this->getTvStats($hariini, $periodInfo['startDate'], $periodInfo['endDate']);

        // 4. Data Bottom Grid (Leaderboard Harian per Cabang) - OPTIMIZED
        $leaderboardsByCabang = $this->getBranchLeaderboards($hariini, false, $periodInfo['startDate'], $periodInfo['endDate'], null);
        $kpiLeaderboardsByCabang = $this->getKpiBranchLeaderboards($hariini, false, $periodInfo['startDate'], $periodInfo['endDate'], null);

        // 5. Data Registrasi Token
        $regToken = RegistrationToken::getLatestToken();

        return view('admin.dashboard.dashboardtv', array_merge(
            $headerStats,
            $tvStats,
            compact('leaderboardsByCabang', 'kpiLeaderboardsByCabang', 'regToken'),
            ['periodeLabel' => $periodInfo['label'], 'startDate' => $periodInfo['startDate'], 'endDate' => $periodInfo['endDate']]
        ));
    }

    // =========================================================================
    // PRIVATE HELPER METHODS (LOGIC INTI)
    // =========================================================================

    /**
     * Helper: Menentukan Range Tanggal (Cutoff 26 - 25)
     */
    private function getPeriodRange(Request $request)
    {
        $hariIni = CarbonImmutable::today();
        $requested = (string) $request->query('periode', '');

        // Parameter "periode" berisi tanggal awal periode (Y-m-d); selain itu pakai periode berjalan.
        $periode = preg_match('/^\d{4}-\d{2}-\d{2}$/', $requested)
            ? PeriodeKerja::dari($requested)
            : PeriodeKerja::dari($hariIni);

        $startDate = $periode->mulai->toDateString();
        $endDate = $periode->selesai->min($hariIni)->toDateString();
        $label = $periode->mulai->translatedFormat('d F').' - '.CarbonImmutable::parse($endDate)->translatedFormat('d F Y');

        return compact('startDate', 'endDate', 'label');
    }

    /**
     * 12 periode terakhir (terbaru dulu) untuk dropdown filter: [tanggal mulai => label].
     */
    private function getPeriodeOptions(): array
    {
        $bulanIni = PeriodeKerja::dari()->selesai;

        return collect(range(0, 11))
            ->mapWithKeys(function ($i) use ($bulanIni) {
                $bulan = $bulanIni->subMonthsNoOverflow($i);
                $periode = PeriodeKerja::bulan($bulan->month, $bulan->year);

                return [$periode->mulai->toDateString() => $periode->mulai->translatedFormat('d F').' - '.$periode->selesai->translatedFormat('d F Y')];
            })
            ->all();
    }

    /**
     * Helper: Filter Query Cabang untuk Admin
     */
    private function applyCabangFilter($query, $userCtx, $tablePrefix = null)
    {
        $cabangColumn = $tablePrefix ? $tablePrefix.'.kode_cabang' : 'kode_cabang';
        $deptColumn = $tablePrefix ? $tablePrefix.'.kode_dept' : 'kode_dept';

        if (! empty($userCtx['filterCabang'])) {
            $query->where($cabangColumn, $userCtx['filterCabang']);
        } elseif ($userCtx['isAdminCabang'] && $userCtx['kodeCabang']) {
            $query->where($cabangColumn, $userCtx['kodeCabang']);
        }

        if (! empty($userCtx['filterDept'])) {
            $query->where($deptColumn, $userCtx['filterDept']);
        }

        return $query;
    }

    private function applyKeywordFilter($query, $userCtx, $tablePrefix = null)
    {
        $keyword = strtolower(trim((string) ($userCtx['filterQ'] ?? '')));
        if ($keyword === '') {
            return $query;
        }

        $nikColumn = $tablePrefix ? $tablePrefix.'.nik' : 'nik';
        $namaColumn = $tablePrefix ? $tablePrefix.'.nama_lengkap' : 'nama_lengkap';

        $query->where(function ($sub) use ($nikColumn, $namaColumn, $keyword) {
            $sub->whereRaw("LOWER({$nikColumn}) LIKE ?", ["%{$keyword}%"])
                ->orWhereRaw("LOWER({$namaColumn}) LIKE ?", ["%{$keyword}%"]);
        });

        return $query;
    }

    /**
     * Logic: Statistik Realtime Admin
     */
    private function getRealtimeStats($ctx, $userCtx)
    {
        $today = Carbon::parse($ctx['today']);

        // Presensi
        $queryPresensi = Presensi::query()
            ->join('karyawan', 'presensi.nik', '=', 'karyawan.nik')
            ->leftJoin('jam_kerja', 'presensi.kode_jam_kerja', '=', 'jam_kerja.kode_jam_kerja')
            ->where('presensi.tgl_presensi', $ctx['today']);
        $this->applyCabangFilter($queryPresensi, $userCtx, 'karyawan');

        $rekappresensi = $queryPresensi->selectRaw("
            COUNT(CASE WHEN presensi.status='h' AND presensi.jam_in!='00:00:00' THEN 1 END) AS jmlhadir,
            SUM(CASE WHEN presensi.status='h' AND presensi.jam_in!='00:00:00' AND jam_kerja.jam_masuk IS NOT NULL AND presensi.jam_in > jam_kerja.jam_masuk THEN 1 ELSE 0 END) AS jmlterlambat
        ")->first();

        // Izin/Sakit/Cuti
        $queryIzin = Presensi::query()->where('tgl_presensi', $ctx['today']);
        if ($userCtx['isAdminCabang']) {
            $queryIzin->join('karyawan', 'presensi.nik', '=', 'karyawan.nik');
            $this->applyCabangFilter($queryIzin, $userCtx, 'karyawan');
        }
        $rekapizin = $queryIzin->selectRaw("
            SUM(CASE WHEN presensi.status = 'i' THEN 1 ELSE 0 END) as jmlizin,
            SUM(CASE WHEN presensi.status = 's' THEN 1 ELSE 0 END) as jmlsakit,
            SUM(CASE WHEN presensi.status = 'c' THEN 1 ELSE 0 END) as jmlcuti,
            SUM(CASE WHEN presensi.status = 'r' THEN 1 ELSE 0 END) as jmlroster
        ")->first();

        // Karyawan & Alpha
        $queryKaryawan = Karyawan::where('status_aktif', 'Aktif');
        $this->applyCabangFilter($queryKaryawan, $userCtx);
        $jmlkaryawan = $queryKaryawan->count();

        $jmlhadir = $rekappresensi->jmlhadir ?? 0;
        $jmlterlambat = $rekappresensi->jmlterlambat ?? 0;
        $jmlizin = $rekapizin->jmlizin ?? 0;
        $jmlsakit = $rekapizin->jmlsakit ?? 0;
        $jmlcuti = $rekapizin->jmlcuti ?? 0;
        $jmlroster = $rekapizin->jmlroster ?? 0;

        $queryDinasLuar = DinasLuar::query()
            ->join('karyawan', 'dinas_luar.nik', '=', 'karyawan.nik')
            ->where('dinas_luar.status_acc', 'acc')
            ->whereDate('dinas_luar.tgl_mulai', '<=', $ctx['today'])
            ->whereDate('dinas_luar.tgl_selesai', '>=', $ctx['today']);
        $this->applyCabangFilter($queryDinasLuar, $userCtx, 'karyawan');
        $jmlDinasLuar = $queryDinasLuar->count();

        $jmltidakabsen = max($jmlkaryawan - ($jmlhadir + $jmlizin + $jmlsakit + $jmlcuti + $jmlroster + $jmlDinasLuar), 0);
        $jmlTanpaKeterangan = $jmltidakabsen;

        $prevWeekDate = $today->copy()->subDays(7);

        $qPrevPresensi = Presensi::query()
            ->join('karyawan', 'presensi.nik', '=', 'karyawan.nik')
            ->leftJoin('jam_kerja', 'presensi.kode_jam_kerja', '=', 'jam_kerja.kode_jam_kerja')
            ->where('presensi.tgl_presensi', $prevWeekDate->format('Y-m-d'));
        $this->applyCabangFilter($qPrevPresensi, $userCtx, 'karyawan');

        $prevWeekPresensi = $qPrevPresensi->selectRaw("\n            COUNT(CASE WHEN presensi.status='h' AND presensi.jam_in!='00:00:00' THEN 1 END) AS jmlhadir,\n            SUM(CASE WHEN presensi.status='h' AND presensi.jam_in!='00:00:00' AND jam_kerja.jam_masuk IS NOT NULL AND presensi.jam_in > jam_kerja.jam_masuk THEN 1 ELSE 0 END) AS jmlterlambat,\n            SUM(CASE WHEN presensi.status='i' THEN 1 ELSE 0 END) AS jmlizin,\n            SUM(CASE WHEN presensi.status='s' THEN 1 ELSE 0 END) AS jmlsakit,\n            SUM(CASE WHEN presensi.status='c' THEN 1 ELSE 0 END) AS jmlcuti,\n            SUM(CASE WHEN presensi.status='r' THEN 1 ELSE 0 END) AS jmlroster\n        ")->first();

        $qPrevDinasLuar = DinasLuar::query()
            ->join('karyawan', 'dinas_luar.nik', '=', 'karyawan.nik')
            ->where('dinas_luar.status_acc', 'acc')
            ->whereDate('dinas_luar.tgl_mulai', '<=', $prevWeekDate->format('Y-m-d'))
            ->whereDate('dinas_luar.tgl_selesai', '>=', $prevWeekDate->format('Y-m-d'));
        $this->applyCabangFilter($qPrevDinasLuar, $userCtx, 'karyawan');
        $prevWeekDinasLuar = (int) $qPrevDinasLuar->count();

        $prevValues = [
            'hadir' => (int) ($prevWeekPresensi->jmlhadir ?? 0),
            'terlambat' => (int) ($prevWeekPresensi->jmlterlambat ?? 0),
            'izin' => (int) ($prevWeekPresensi->jmlizin ?? 0),
            'sakit' => (int) ($prevWeekPresensi->jmlsakit ?? 0),
            'cuti' => (int) ($prevWeekPresensi->jmlcuti ?? 0),
            'roster' => (int) ($prevWeekPresensi->jmlroster ?? 0),
            'dinas_luar' => $prevWeekDinasLuar,
        ];
        $prevValues['tanpa_keterangan'] = max(
            $jmlkaryawan - (
                $prevValues['hadir'] +
                $prevValues['izin'] +
                $prevValues['sakit'] +
                $prevValues['cuti'] +
                $prevValues['roster'] +
                $prevValues['dinas_luar']
            ),
            0
        );

        $currentValues = [
            'hadir' => $jmlhadir,
            'terlambat' => $jmlterlambat,
            'izin' => $jmlizin,
            'sakit' => $jmlsakit,
            'cuti' => $jmlcuti,
            'roster' => $jmlroster,
            'dinas_luar' => $jmlDinasLuar,
            'tanpa_keterangan' => $jmlTanpaKeterangan,
        ];

        $realtimeComparison = [];
        foreach ($currentValues as $metric => $currentValue) {
            $prevWeekValue = (float) ($prevValues[$metric] ?? 0);
            $delta = round($currentValue - $prevWeekValue, 1);

            $realtimeComparison[$metric] = [
                'prev_week' => $prevWeekValue,
                'prev_week_date' => $prevWeekDate->format('Y-m-d'),
                'delta' => $delta,
                'delta_pct' => $prevWeekValue > 0 ? round(($delta / $prevWeekValue) * 100, 1) : null,
            ];
        }

        return compact(
            'rekappresensi',
            'rekapizin',
            'jmlhadir',
            'jmlterlambat',
            'jmlizin',
            'jmlsakit',
            'jmlcuti',
            'jmlroster',
            'jmlDinasLuar',
            'jmlTanpaKeterangan',
            'jmlkaryawan',
            'jmltidakabsen',
            'realtimeComparison'
        );
    }

    /**
     * Logic: Trend 7 hari untuk cards realtime
     */
    private function getRealtimeTrends($ctx, $userCtx)
    {
        $trendHadir = [];
        $trendTerlambat = [];
        $trendIzin = [];
        $trendAlpha = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::parse($ctx['today'])->subDays($i)->format('Y-m-d');

            $qPresensi = Presensi::query()
                ->join('karyawan', 'presensi.nik', '=', 'karyawan.nik')
                ->leftJoin('jam_kerja', 'presensi.kode_jam_kerja', '=', 'jam_kerja.kode_jam_kerja')
                ->where('presensi.tgl_presensi', $date);
            $this->applyCabangFilter($qPresensi, $userCtx, 'karyawan');

            $daily = $qPresensi->selectRaw("\n                COUNT(CASE WHEN presensi.status='h' AND presensi.jam_in!='00:00:00' THEN 1 END) AS hadir,\n                SUM(CASE WHEN presensi.status='h' AND presensi.jam_in!='00:00:00' AND jam_kerja.jam_masuk IS NOT NULL AND presensi.jam_in > jam_kerja.jam_masuk THEN 1 ELSE 0 END) AS terlambat,\n                SUM(CASE WHEN presensi.status IN ('i','s','c','r') THEN 1 ELSE 0 END) AS izin_total\n            ")->first();

            $qKaryawan = Karyawan::query()->where('status_aktif', 'Aktif');
            $this->applyCabangFilter($qKaryawan, $userCtx);
            $karyawanAktif = $qKaryawan->count();

            $hadir = (int) ($daily->hadir ?? 0);
            $terlambat = (int) ($daily->terlambat ?? 0);
            $izin = (int) ($daily->izin_total ?? 0);
            $alpha = max($karyawanAktif - ($hadir + $izin), 0);

            $trendHadir[] = $hadir;
            $trendTerlambat[] = $terlambat;
            $trendIzin[] = $izin;
            $trendAlpha[] = $alpha;
        }

        return compact('trendHadir', 'trendTerlambat', 'trendIzin', 'trendAlpha');
    }

    /**
     * Logic: Data KPI visual untuk dashboard admin
     */
    private function getKpiVisualStats($ctx, $userCtx)
    {
        $startDate = PeriodeKerja::dari($ctx['today'])->mulai->toDateString();
        $endDate = $ctx['today'];

        $baseKpiQuery = KpiLeaderboardSnapshot::query()
            ->whereBetween('kpi_leaderboard_snapshots.date', [$startDate, $endDate]);

        if ($userCtx['isAdminCabang'] && $userCtx['kodeCabang']) {
            $baseKpiQuery->where('kpi_leaderboard_snapshots.kode_cabang', $userCtx['kodeCabang']);
        }

        $kpiByCabangQuery = (clone $baseKpiQuery)
            ->leftJoin('cabang', 'kpi_leaderboard_snapshots.kode_cabang', '=', 'cabang.kode_cabang')
            ->selectRaw('COALESCE(cabang.nama_cabang, kpi_leaderboard_snapshots.kode_cabang) as cabang, SUM(kpi_leaderboard_snapshots.points) as total_points')
            ->groupByRaw('COALESCE(cabang.nama_cabang, kpi_leaderboard_snapshots.kode_cabang)')
            ->orderByDesc('total_points')
            ->limit(8)
            ->get();

        $kpiCabangLabels = $kpiByCabangQuery->pluck('cabang')->values();
        $kpiCabangSeries = $kpiByCabangQuery->pluck('total_points')->map(fn ($v) => (int) round($v))->values();

        $kpiLeaderboardQuery = (clone $baseKpiQuery)
            ->join('karyawan', 'kpi_leaderboard_snapshots.nik', '=', 'karyawan.nik')
            ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
            ->selectRaw('kpi_leaderboard_snapshots.nik, kpi_leaderboard_snapshots.nama_lengkap, SUM(kpi_leaderboard_snapshots.points) as total_points, karyawan.foto, jabatan.nama_jabatan as jabatan_nama')
            ->groupBy('kpi_leaderboard_snapshots.nik', 'kpi_leaderboard_snapshots.nama_lengkap', 'karyawan.foto', 'jabatan.nama_jabatan')
            ->orderByDesc('total_points')
            ->limit(5)
            ->get();

        $qKpiPending = KPIDaily::query()
            ->join('karyawan', 'kpi_daily.nik', '=', 'karyawan.nik')
            ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
            ->whereNull('kpi_daily.approve_hr')
            ->select('kpi_daily.id', 'kpi_daily.nik', 'kpi_daily.tanggal', 'karyawan.nama_lengkap', 'karyawan.foto', 'jabatan.nama_jabatan as jabatan_nama')
            ->orderByDesc('kpi_daily.tanggal')
            ->limit(10);
        $this->applyCabangFilter($qKpiPending, $userCtx, 'karyawan');
        $this->applyKeywordFilter($qKpiPending, $userCtx, 'karyawan');
        $kpiPending = $qKpiPending->get();

        return compact('kpiCabangLabels', 'kpiCabangSeries', 'kpiLeaderboardQuery', 'kpiPending');
    }

    /**
     * Logic: Data dropdown filter global
     */
    private function getGlobalFilters($userCtx)
    {
        $qCabang = Cabang::query()->orderBy('nama_cabang');
        if ($userCtx['isAdminCabang'] && $userCtx['kodeCabang']) {
            $qCabang->where('kode_cabang', $userCtx['kodeCabang']);
        }
        $cabangFilterOptions = $qCabang->get(['kode_cabang', 'nama_cabang']);
        $departemenFilterOptions = Departemen::query()->orderBy('nama_dept')->get(['kode_dept', 'nama_dept']);

        return compact('cabangFilterOptions', 'departemenFilterOptions');
    }

    /**
     * Logic: Notifikasi kritis dashboard
     */
    private function getCriticalNotifications($ctx, $userCtx, $statsRealtime)
    {
        $today = Carbon::parse($ctx['today']);
        $warningDate = $today->copy()->addDays(7)->format('Y-m-d');

        $qSpWarning = DB::table('surat_peringatan')
            ->join('karyawan', 'surat_peringatan.nik', '=', 'karyawan.nik')
            ->whereDate('surat_peringatan.expires_at', '>=', $today->format('Y-m-d'))
            ->whereDate('surat_peringatan.expires_at', '<=', $warningDate)
            ->where('karyawan.status_aktif', 'Aktif');
        $this->applyCabangFilter($qSpWarning, $userCtx, 'karyawan');
        $spWarningCount = $qSpWarning->count();

        $lateMassiveCount = (int) ($statsRealtime['jmlterlambat'] ?? 0);
        $isLateMassive = $lateMassiveCount >= 15;

        $criticalCount = $spWarningCount + ($isLateMassive ? 1 : 0);

        return compact('spWarningCount', 'lateMassiveCount', 'isLateMassive', 'criticalCount');
    }

    /**
     * Logic: Culture & Daily Widgets (Pengumuman, Mini Calendar, Momen Spesial)
     */
    private function getCultureAndDailyWidgets($ctx, $userCtx, $selectedMonth = null)
    {
        $today = Carbon::parse($ctx['today']);
        $calendarRef = $today->copy();
        if ($selectedMonth && preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $calendarRef = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        }

        $monthStart = $calendarRef->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();
        $monthEnd = $calendarRef->copy()->endOfMonth();

        // 1) Pengumuman real by month window
        $pengumumanAktif = Pengumuman::query()
            ->where(function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('tanggal_mulai', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
                    ->orWhereBetween('tanggal_selesai', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
                    ->orWhere(function ($sub) use ($monthStart, $monthEnd) {
                        $sub->whereDate('tanggal_mulai', '<=', $monthStart->format('Y-m-d'))
                            ->whereDate('tanggal_selesai', '>=', $monthEnd->format('Y-m-d'));
                    });
            })
            ->orderByDesc('is_active')
            ->orderByDesc('tanggal_mulai')
            ->limit(10)
            ->get();

        // 2) Event mini calendar: holiday, leave, KPI cutoff
        $holidayRows = HariLibur::query()
            ->whereBetween('tanggal_libur', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
            ->get(['tanggal_libur', 'keterangan']);

        $leaveQuery = Izin::query()
            ->join('karyawan', 'izin.nik', '=', 'karyawan.nik')
            ->where('izin.status_approved', 1)
            ->whereDate('izin.tgl_izin_dari', '<=', $monthEnd->format('Y-m-d'))
            ->whereDate('izin.tgl_izin_sampai', '>=', $monthStart->format('Y-m-d'));
        $this->applyCabangFilter($leaveQuery, $userCtx, 'karyawan');
        $leaveRows = $leaveQuery->get([
            'izin.tgl_izin_dari',
            'izin.tgl_izin_sampai',
            'izin.status',
            'karyawan.nik',
            'karyawan.nama_lengkap',
            'karyawan.foto',
        ]);

        $dinasQuery = DinasLuar::query()
            ->join('karyawan', 'dinas_luar.nik', '=', 'karyawan.nik')
            ->where('dinas_luar.status_acc', 'acc')
            ->whereDate('dinas_luar.tgl_mulai', '<=', $monthEnd->format('Y-m-d'))
            ->whereDate('dinas_luar.tgl_selesai', '>=', $monthStart->format('Y-m-d'));
        $this->applyCabangFilter($dinasQuery, $userCtx, 'karyawan');
        $dinasRows = $dinasQuery->get([
            'dinas_luar.tgl_mulai',
            'dinas_luar.tgl_selesai',
            'dinas_luar.lokasi_tujuan',
            'karyawan.nik',
            'karyawan.nama_lengkap',
            'karyawan.foto',
        ]);

        $calendarEvents = [];
        $calendarDailyDetails = [];

        $ensureCalendarDay = function (&$arr, $key) {
            if (! isset($arr[$key])) {
                $arr[$key] = [
                    'holiday' => false,
                    'leave' => 0,
                    'izin' => 0,
                    'sakit' => 0,
                    'cuti' => 0,
                    'dinas' => 0,
                    'kpi_cutoff' => false,
                ];
            }
        };

        $ensureDetailsDay = function (&$arr, $key) {
            if (! isset($arr[$key])) {
                $arr[$key] = [
                    'izin' => [],
                    'sakit' => [],
                    'cuti' => [],
                    'dinas' => [],
                ];
            }
        };

        foreach ($holidayRows as $h) {
            $key = Carbon::parse($h->tanggal_libur)->format('Y-m-d');
            $ensureCalendarDay($calendarEvents, $key);
            $calendarEvents[$key]['holiday'] = true;
        }

        foreach ($leaveRows as $row) {
            $from = Carbon::parse($row->tgl_izin_dari)->max($monthStart);
            $to = Carbon::parse($row->tgl_izin_sampai)->min($monthEnd);

            $status = strtolower((string) ($row->status ?? 'i'));
            $type = $status === 's' ? 'sakit' : ($status === 'c' ? 'cuti' : 'izin');
            $person = [
                'nik' => $row->nik,
                'nama_lengkap' => $row->nama_lengkap,
                'foto' => $row->foto,
                'keterangan' => strtoupper($type),
            ];

            for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
                $key = $d->format('Y-m-d');
                $ensureCalendarDay($calendarEvents, $key);
                $ensureDetailsDay($calendarDailyDetails, $key);
                $calendarEvents[$key]['leave']++;
                $calendarEvents[$key][$type]++;
                $calendarDailyDetails[$key][$type][] = $person;
            }
        }

        foreach ($dinasRows as $row) {
            $from = Carbon::parse($row->tgl_mulai)->max($monthStart);
            $to = Carbon::parse($row->tgl_selesai)->min($monthEnd);
            $person = [
                'nik' => $row->nik,
                'nama_lengkap' => $row->nama_lengkap,
                'foto' => $row->foto,
                'keterangan' => $row->lokasi_tujuan ?: '-',
            ];

            for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
                $key = $d->format('Y-m-d');
                $ensureCalendarDay($calendarEvents, $key);
                $ensureDetailsDay($calendarDailyDetails, $key);
                $calendarEvents[$key]['dinas']++;
                $calendarDailyDetails[$key]['dinas'][] = $person;
            }
        }

        $kpiCutoffDate = Carbon::create($today->year, $today->month, min(25, $monthEnd->day));
        $cutoffKey = $kpiCutoffDate->format('Y-m-d');
        $ensureCalendarDay($calendarEvents, $cutoffKey);
        $calendarEvents[$cutoffKey]['kpi_cutoff'] = true;

        // 3) Culture widgets: birthday + work anniversary (month-aware)
        $birthdayQuery = Karyawan::query()
            ->with(['cabang:kode_cabang,nama_cabang', 'departemen:kode_dept,nama_dept'])
            ->where('status_aktif', 'Aktif')
            ->whereRaw('EXTRACT(MONTH FROM tanggal_lahir) = ?', [$calendarRef->month])
            ->orderByRaw('EXTRACT(DAY FROM tanggal_lahir) asc');

        if ($calendarRef->isSameMonth($today) && $calendarRef->year === $today->year) {
            $birthdayQuery->whereRaw('EXTRACT(DAY FROM tanggal_lahir) >= ?', [$today->day]);
        }

        $this->applyCabangFilter($birthdayQuery, $userCtx);
        $ulangTahunBulanIni = $birthdayQuery->get(['nik', 'nama_lengkap', 'tanggal_lahir', 'foto', 'kode_cabang', 'kode_dept']);

        $annivQuery = Karyawan::query()
            ->where('status_aktif', 'Aktif')
            ->whereNotNull('tanggal_awal_kontrak')
            ->whereRaw('EXTRACT(MONTH FROM tanggal_awal_kontrak) = ?', [$calendarRef->month])
            ->orderByRaw('EXTRACT(DAY FROM tanggal_awal_kontrak) asc')
            ->limit(8);
        $this->applyCabangFilter($annivQuery, $userCtx);
        $anniversaryBulanIni = $annivQuery->get(['nik', 'nama_lengkap', 'tanggal_awal_kontrak', 'foto']);

        $calendarLabel = $monthStart->translatedFormat('F Y');
        $calendarMonth = $monthStart->format('Y-m');
        $prevMonth = $monthStart->copy()->subMonth()->format('Y-m');
        $nextMonth = $monthStart->copy()->addMonth()->format('Y-m');

        return compact(
            'pengumumanAktif',
            'calendarEvents',
            'calendarDailyDetails',
            'calendarLabel',
            'calendarMonth',
            'prevMonth',
            'nextMonth',
            'kpiCutoffDate',
            'ulangTahunBulanIni',
            'anniversaryBulanIni'
        );
    }

    /**
     * Logic: Demografi Admin
     */
    private function getDemographics($userCtx)
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
    private function getTurnoverStats($ctx, $userCtx, $jmlKaryawanAktifSaatIni)
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

    /**
     * Logic: List Data Admin (Modals)
     */
    private function getDataLists($ctx, $userCtx)
    {
        // Karyawan Aktif
        $qAktif = Karyawan::where('status_aktif', 'Aktif')->with('jabatanRel')
            ->select('nik', 'nama_lengkap', 'jabatan_id', 'kode_cabang', 'tanggal_awal_kontrak', 'foto')
            ->orderBy('nama_lengkap', 'asc');
        $this->applyCabangFilter($qAktif, $userCtx);
        $this->applyKeywordFilter($qAktif, $userCtx);
        $daftarKaryawanAktif = $qAktif->get();

        // Definisikan rentang 3 bulan (Bulan terpilih + 2 bulan sebelumnya)
        $selectedDate = Carbon::create($ctx['year'], $ctx['month'], 1);
        $threeMonthsAgo = $selectedDate->copy()->subMonths(2)->startOfMonth();
        $selectedMonthEnd = $selectedDate->copy()->endOfMonth();

        // Karyawan Masuk (Pilih yang lebih cepat antara TMT atau Tanggal Awal Kontrak)
        $qMasuk = Karyawan::with(['jabatanRel', 'cabang'])
            ->whereRaw('LEAST(COALESCE(tmt, tanggal_awal_kontrak), COALESCE(tanggal_awal_kontrak, tmt)) BETWEEN ? AND ?', [
                $threeMonthsAgo->format('Y-m-d'),
                $selectedMonthEnd->format('Y-m-d'),
            ])
            ->orderByRaw('LEAST(COALESCE(tmt, tanggal_awal_kontrak), COALESCE(tanggal_awal_kontrak, tmt)) DESC');
        $this->applyCabangFilter($qMasuk, $userCtx);
        $this->applyKeywordFilter($qMasuk, $userCtx);
        $karyawanMasuk = $qMasuk->get();

        // Karyawan Keluar (Murni berdasarkan status)
        $qKeluar = Karyawan::with(['jabatanRel', 'cabang'])
            ->whereIn('status_aktif', [Karyawan::STATUS_NONAKTIF, Karyawan::STATUS_DIBERHENTIKAN])
            ->orderByRaw('tanggal_keluar DESC NULLS LAST')
            ->orderBy('updated_at', 'desc');
        $this->applyCabangFilter($qKeluar, $userCtx);
        $this->applyKeywordFilter($qKeluar, $userCtx);
        $karyawanKeluar = $qKeluar->get();

        // Approval Lists
        $qIzin = Izin::query()->join('karyawan', 'izin.nik', '=', 'karyawan.nik')
            ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
            ->leftJoin('master_cuti', 'izin.kode_cuti', '=', 'master_cuti.kode_cuti')
            ->where('izin.status_approved', '0')
            ->select('izin.*', 'karyawan.nama_lengkap', 'karyawan.foto', 'jabatan.nama_jabatan as jabatan_nama', 'master_cuti.nama_cuti')
            ->orderBy('izin.created_at', 'desc')->limit(10);
        $this->applyCabangFilter($qIzin, $userCtx, 'karyawan');
        $this->applyKeywordFilter($qIzin, $userCtx, 'karyawan');
        $izinPending = $qIzin->get();

        $qLembur = Lembur::query()->join('karyawan', 'lembur.nik', '=', 'karyawan.nik')
            ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
            ->where('lembur.status_approved', 0)
            ->select('lembur.*', 'karyawan.nama_lengkap', 'karyawan.foto', 'jabatan.nama_jabatan as jabatan_nama')
            ->orderBy('lembur.created_at', 'desc')->limit(10);
        $this->applyCabangFilter($qLembur, $userCtx, 'karyawan');
        $this->applyKeywordFilter($qLembur, $userCtx, 'karyawan');
        $lemburPending = $qLembur->get();

        $qDinas = DinasLuar::query()->join('karyawan', 'dinas_luar.nik', '=', 'karyawan.nik')
            ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
            ->where('dinas_luar.status_acc', 'menunggu')
            ->select('dinas_luar.*', 'karyawan.nama_lengkap', 'karyawan.foto', 'jabatan.nama_jabatan as jabatan_nama')
            ->orderBy('dinas_luar.created_at', 'desc')->limit(10);
        $this->applyCabangFilter($qDinas, $userCtx, 'karyawan');
        $this->applyKeywordFilter($qDinas, $userCtx, 'karyawan');
        $dinasLuarPending = $qDinas->get();

        // Karyawan Sisa Kontrak (PKWT aktif, kontrak habis dalam 3 bulan ke depan)
        $today = Carbon::parse($ctx['today']);
        $threeMonthsLater = $today->copy()->addMonths(3)->endOfMonth();
        $qSisaKontrak = Karyawan::with(['jabatanRel', 'cabang'])
            ->where('status_aktif', 'Aktif')
            ->where('status_karyawan', 'PKWT')
            ->whereNotNull('tanggal_habis_kontrak')
            ->whereDate('tanggal_habis_kontrak', '<=', $threeMonthsLater->format('Y-m-d'))
            ->select('nik', 'nama_lengkap', 'jabatan_id', 'kode_cabang', 'tanggal_awal_kontrak', 'tmt', 'tanggal_habis_kontrak', 'status_karyawan', 'foto')
            ->orderBy('tanggal_habis_kontrak', 'asc');
        $this->applyCabangFilter($qSisaKontrak, $userCtx);
        $karyawanSisaKontrak = $qSisaKontrak->get();

        return compact('daftarKaryawanAktif', 'karyawanMasuk', 'karyawanKeluar', 'karyawanSisaKontrak', 'izinPending', 'lemburPending', 'dinasLuarPending');
    }

    /**
     * Logic: Ringkasan Request BPJS & Kenaikan Gaji
     */
    private function getRequestSummaryStats($userCtx)
    {
        $qBpjs = BpjsRequest::query()
            ->join('karyawan', 'bpjs_tk_requests.nik', '=', 'karyawan.nik')
            ->where('bpjs_tk_requests.status', 'pending');
        $this->applyCabangFilter($qBpjs, $userCtx, 'karyawan');
        $this->applyKeywordFilter($qBpjs, $userCtx, 'karyawan');

        $qSalary = SalaryIncrease::query()
            ->join('karyawan', 'salary_increases.nik', '=', 'karyawan.nik')
            ->where('salary_increases.status', 'pending');
        $this->applyCabangFilter($qSalary, $userCtx, 'karyawan');
        $this->applyKeywordFilter($qSalary, $userCtx, 'karyawan');

        $bpjsPendingList = (clone $qBpjs)
            ->select(
                'bpjs_tk_requests.id',
                'bpjs_tk_requests.nik',
                'bpjs_tk_requests.requested_at',
                'bpjs_tk_requests.created_at',
                'karyawan.nama_lengkap',
                'karyawan.foto'
            )
            ->orderByDesc('bpjs_tk_requests.created_at')
            ->limit(6)
            ->get();

        $salaryIncreasePendingList = (clone $qSalary)
            ->select(
                'salary_increases.id',
                'salary_increases.nik',
                'salary_increases.tanggal_pengajuan',
                'salary_increases.created_at',
                'salary_increases.persentase',
                'karyawan.nama_lengkap',
                'karyawan.foto'
            )
            ->orderByDesc('salary_increases.created_at')
            ->limit(6)
            ->get();

        return [
            'bpjsPendingCount' => $qBpjs->count(),
            'salaryIncreasePendingCount' => $qSalary->count(),
            'bpjsPendingList' => $bpjsPendingList,
            'salaryIncreasePendingList' => $salaryIncreasePendingList,
        ];
    }

    /**
     * Logic: Header Stats untuk Overview & TV
     */
    private function getDailyHeaderStats($date)
    {
        // Presensi untuk Hadir & Terlambat
        $rekappresensi = DB::table('presensi')
            ->leftJoin('jam_kerja', 'presensi.kode_jam_kerja', '=', 'jam_kerja.kode_jam_kerja')
            ->where('tgl_presensi', $date)
            ->selectRaw("
                COUNT(CASE WHEN presensi.status = 'h' THEN 1 END) as jmlhadir,
                SUM(CASE WHEN presensi.status = 'h' AND presensi.jam_in != '00:00:00' AND jam_kerja.jam_masuk IS NOT NULL AND presensi.jam_in > jam_kerja.jam_masuk THEN 1 ELSE 0 END) as jmlterlambat
            ")
            ->first();

        // Izin/Sakit/Cuti - gunakan izin table (konsisten dengan cards)
        $rekapizinsakit = DB::table('izin')
            ->where(function ($query) use ($date) {
                $query->where('izin.status_approved', '0')  // Pending
                    ->orWhere(function ($q) use ($date) {
                        $q->where('izin.status_approved', '1')->whereRaw('? BETWEEN izin.tgl_izin_dari AND izin.tgl_izin_sampai', [$date]);  // Approved & berlangsung
                    })
                    ->orWhere(function ($q) use ($date) {
                        $q->where('izin.status_approved', '2')->whereDate('izin.created_at', '=', $date);  // Rejected hari ini
                    });
            })
            ->selectRaw("
                SUM(CASE WHEN izin.status = 'i' THEN 1 ELSE 0 END) as jmlizin,
                SUM(CASE WHEN izin.status = 's' THEN 1 ELSE 0 END) as jmlsakit,
                SUM(CASE WHEN izin.status = 'c' THEN 1 ELSE 0 END) as jmlcuti
            ")
            ->first();

        return [
            'jmlhadir' => $rekappresensi->jmlhadir ?? 0,
            'jmlterlambat' => $rekappresensi->jmlterlambat ?? 0,
            'jmlizin' => $rekapizinsakit->jmlizin ?? 0,
            'jmlsakit' => $rekapizinsakit->jmlsakit ?? 0,
            'jmlcuti' => $rekapizinsakit->jmlcuti ?? 0,
            'rekapizin' => (object) [
                'jmlizin' => $rekapizinsakit->jmlizin ?? 0,
                'jmlsakit' => $rekapizinsakit->jmlsakit ?? 0,
                'jmlcuti' => $rekapizinsakit->jmlcuti ?? 0,
            ],
            // Hitung Belum Absen
            'jmltidakabsen' => DB::table('karyawan')
                ->leftJoin('presensi', function ($join) use ($date) {
                    $join->on('karyawan.nik', '=', 'presensi.nik')->where('presensi.tgl_presensi', '=', $date);
                })
                ->where('karyawan.status_aktif', 'Aktif')
                ->whereNull('presensi.nik')
                ->count(),
        ];
    }

    /**
     * Logic: Overview Lists
     */
    private function getOverviewLists($hariini, $startDate, $endDate)
    {
        // 50 Hadir Terakhir
        $dataPresensi = DB::table('presensi')
            ->join('karyawan', 'presensi.nik', '=', 'karyawan.nik')
            ->leftJoin('cabang', 'karyawan.kode_cabang', '=', 'cabang.kode_cabang')
            ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
            ->where('presensi.tgl_presensi', $hariini)->where('presensi.status', 'h')
            ->select('karyawan.nama_lengkap', 'karyawan.foto', 'jabatan.nama_jabatan as jabatan_nama', 'cabang.nama_cabang', 'presensi.jam_in', 'presensi.jam_out')
            ->orderBy('presensi.jam_in', 'desc')->limit(50)->get();

        // Belum Presensi
        $dataBelumPresensi = DB::table('karyawan')
            ->leftJoin('cabang', 'karyawan.kode_cabang', '=', 'cabang.kode_cabang')
            ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
            ->leftJoin('presensi', function ($join) use ($hariini) {
                $join->on('karyawan.nik', '=', 'presensi.nik')->where('presensi.tgl_presensi', '=', $hariini);
            })
            ->where('karyawan.status_aktif', 'Aktif')->whereNull('presensi.nik')
            ->select('karyawan.nama_lengkap', 'karyawan.foto', 'jabatan.nama_jabatan as jabatan_nama', 'karyawan.no_hp', 'cabang.nama_cabang')
            ->orderBy('karyawan.nama_lengkap', 'asc')->get();

        // Global Daily (Fastest)
        $globalDaily = DB::table('presensi')
            ->join('karyawan', 'presensi.nik', '=', 'karyawan.nik')
            ->leftJoin('cabang', 'karyawan.kode_cabang', '=', 'cabang.kode_cabang')
            ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
            ->select('presensi.jam_in', 'karyawan.nama_lengkap', 'karyawan.foto', 'jabatan.nama_jabatan as jabatan_nama', 'karyawan.kode_cabang', 'cabang.nama_cabang')
            ->where('presensi.tgl_presensi', $hariini)
            ->where('presensi.status', 'h')->where('presensi.jam_in', '!=', '00:00:00')
            ->orderBy('presensi.jam_in', 'asc')->limit(20)->get();

        // Top Performance (Bulanan) - Dipisah Office vs Site
        $dateObj = Carbon::parse($endDate);
        $bulan = $dateObj->month;
        $tahun = $dateObj->year;

        $hasRekap = RekapBulanan::where('bulan', $bulan)->where('tahun', $tahun)->exists();

        if ($hasRekap) {
            $topQueryBase = RekapBulanan::query()
                ->select(
                    'rekap_bulanans.nik',
                    'karyawan.nama_lengkap',
                    'rekap_bulanans.total_poin as total_points',
                    'karyawan.foto',
                    'jabatan.nama_jabatan as jabatan_nama',
                    'cabang.nama_cabang',
                    'rekap_bulanans.avg_jam_masuk'
                )
                ->join('karyawan', 'rekap_bulanans.nik', '=', 'karyawan.nik')
                ->leftJoin('cabang', 'karyawan.kode_cabang', '=', 'cabang.kode_cabang')
                ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
                ->where('rekap_bulanans.bulan', $bulan)
                ->where('rekap_bulanans.tahun', $tahun)
                ->orderByDesc('rekap_bulanans.total_poin')
                ->orderBy('rekap_bulanans.avg_jam_masuk', 'asc');

            $topOffice = (clone $topQueryBase)->whereNotIn('karyawan.kode_cabang', $this->siteBranches)->limit(10)->get();
            $topSite = (clone $topQueryBase)->whereIn('karyawan.kode_cabang', $this->siteBranches)->limit(10)->get();
        } else {
            $rawQuery = LeaderboardSnapshot::query()
                ->select('leaderboard_snapshots.nik', 'leaderboard_snapshots.nama_lengkap', DB::raw('SUM(leaderboard_snapshots.points) as total_points'), 'karyawan.foto', 'jabatan.nama_jabatan as jabatan_nama', 'cabang.nama_cabang', 'leaderboard_snapshots.kode_cabang')
                ->join('karyawan', 'leaderboard_snapshots.nik', '=', 'karyawan.nik')
                ->leftJoin('cabang', 'karyawan.kode_cabang', '=', 'cabang.kode_cabang')
                ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
                ->whereBetween('leaderboard_snapshots.date', [$startDate, $endDate])
                ->groupBy('leaderboard_snapshots.nik', 'leaderboard_snapshots.nama_lengkap', 'karyawan.foto', 'jabatan.nama_jabatan', 'cabang.nama_cabang', 'leaderboard_snapshots.kode_cabang')
                ->get();

            $sortedCollection = $this->applyLiveTieBreaker($rawQuery, $startDate, $endDate, 'total_points');

            $topOffice = $sortedCollection->whereNotIn('kode_cabang', $this->siteBranches)->take(10)->values();
            $topSite = $sortedCollection->whereIn('kode_cabang', $this->siteBranches)->take(10)->values();
        }

        return compact('dataPresensi', 'dataBelumPresensi', 'globalDaily', 'topOffice', 'topSite');
    }

    /**
     * Logic: Overview Activity Lists (Izin/Sakit/Cuti, Lembur, Dinas Luar, SP)
     */
    private function getOverviewActivityLists($hariini)
    {
        $dataIzinSakit = DB::table('izin')
            ->join('karyawan', 'izin.nik', '=', 'karyawan.nik')
            ->leftJoin('cabang', 'karyawan.kode_cabang', '=', 'cabang.kode_cabang')
            ->leftJoin('master_cuti', 'izin.kode_cuti', '=', 'master_cuti.kode_cuti')
            ->where(function ($query) use ($hariini) {
                $query->where('izin.status_approved', '0')
                    ->orWhere(function ($q) use ($hariini) {
                        $q->where('izin.status_approved', '1')->whereRaw('? BETWEEN izin.tgl_izin_dari AND izin.tgl_izin_sampai', [$hariini]);
                    })
                    ->orWhere(function ($q) use ($hariini) {
                        $q->where('izin.status_approved', '2')->whereDate('izin.created_at', '=', $hariini);
                    });
            })
            ->select('karyawan.nama_lengkap', 'karyawan.foto', 'cabang.nama_cabang', 'izin.status', 'izin.keterangan', 'izin.status_approved', 'master_cuti.nama_cuti', 'izin.created_at')
            ->orderBy('izin.status_approved', 'asc')->orderBy('izin.created_at', 'desc')->get();

        $dataLembur = DB::table('lembur')
            ->join('karyawan', 'lembur.nik', '=', 'karyawan.nik')
            ->leftJoin('cabang', 'karyawan.kode_cabang', '=', 'cabang.kode_cabang')
            ->where(function ($query) use ($hariini) {
                $query->where('lembur.status_approved', 0)
                    ->orWhere(function ($q) use ($hariini) {
                        $q->where('lembur.status_approved', 1)->where('lembur.tanggal_lembur', $hariini);
                    })
                    ->orWhere(function ($q) use ($hariini) {
                        $q->where('lembur.status_approved', 2)->whereDate('lembur.created_at', '=', $hariini);
                    });
            })
            ->select('karyawan.nama_lengkap', 'karyawan.foto', 'cabang.nama_cabang', 'lembur.jam_mulai', 'lembur.jam_selesai', 'lembur.pekerjaan', 'lembur.status_approved', 'lembur.created_at', 'lembur.tanggal_lembur')
            ->orderBy('lembur.status_approved', 'asc')->orderBy('lembur.created_at', 'desc')->get();

        $dataDinasLuar = DB::table('dinas_luar')
            ->join('karyawan', 'dinas_luar.nik', '=', 'karyawan.nik')
            ->leftJoin('cabang', 'karyawan.kode_cabang', '=', 'cabang.kode_cabang')
            ->where(function ($query) use ($hariini) {
                $query->where('dinas_luar.status_acc', 'menunggu')
                    ->orWhere(function ($q) use ($hariini) {
                        $q->where('dinas_luar.status_acc', 'acc')->whereDate('dinas_luar.tgl_mulai', '<=', $hariini)->whereDate('dinas_luar.tgl_selesai', '>=', $hariini);
                    })
                    ->orWhere(function ($q) use ($hariini) {
                        $q->where('dinas_luar.status_acc', 'tolak')->whereDate('dinas_luar.created_at', '=', $hariini);
                    });
            })
            ->select('karyawan.nama_lengkap', 'karyawan.foto', 'cabang.nama_cabang', 'dinas_luar.alasan', 'dinas_luar.status_acc', 'dinas_luar.lokasi_tujuan', 'dinas_luar.created_at', 'dinas_luar.tgl_mulai', 'dinas_luar.tgl_selesai')
            ->orderByRaw("CASE WHEN dinas_luar.status_acc = 'menunggu' THEN 0 ELSE 1 END")->orderBy('dinas_luar.created_at', 'desc')->get();

        $karyawanSP = DB::table('surat_peringatan')
            ->join('karyawan', 'surat_peringatan.nik', '=', 'karyawan.nik')
            ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
            ->whereDate('surat_peringatan.expires_at', '>=', $hariini)
            ->where('karyawan.status_aktif', 'Aktif')
            ->orderBy('surat_peringatan.expires_at', 'desc')
            ->select('karyawan.nama_lengkap', 'karyawan.foto', 'jabatan.nama_jabatan as jabatan_nama', 'surat_peringatan.level', 'surat_peringatan.expires_at as sampai_tanggal')->get();

        return compact('dataIzinSakit', 'dataLembur', 'dataDinasLuar', 'karyawanSP');
    }

    /**
     * Logic: TV Stats (Top Office vs Site, Izin, Lembur, dll)
     */
    private function getTvStats($hariini, $startDate, $endDate)
    {
        // 1. TOP PERFORMANCE (Bulanan)
        $dateObj = Carbon::parse($endDate);
        $bulan = $dateObj->month;
        $tahun = $dateObj->year;

        $hasRekap = RekapBulanan::where('bulan', $bulan)->where('tahun', $tahun)->exists();

        if ($hasRekap) {
            $topQueryBase = RekapBulanan::query()
                ->select(
                    'rekap_bulanans.nik',
                    'karyawan.nama_lengkap',
                    'rekap_bulanans.total_poin as total_points',
                    'karyawan.foto',
                    'jabatan.nama_jabatan as jabatan_nama',
                    'cabang.nama_cabang',
                    'rekap_bulanans.avg_jam_masuk'
                )
                ->join('karyawan', 'rekap_bulanans.nik', '=', 'karyawan.nik')
                ->leftJoin('cabang', 'karyawan.kode_cabang', '=', 'cabang.kode_cabang')
                ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
                ->where('rekap_bulanans.bulan', $bulan)
                ->where('rekap_bulanans.tahun', $tahun)
                ->orderByDesc('rekap_bulanans.total_poin')
                ->orderBy('rekap_bulanans.avg_jam_masuk', 'asc');

            $topOffice = (clone $topQueryBase)->whereNotIn('karyawan.kode_cabang', $this->siteBranches)->limit(10)->get();
            $topSite = (clone $topQueryBase)->whereIn('karyawan.kode_cabang', $this->siteBranches)->limit(10)->get();

            // 1b. TOP KPI PERFORMANCE (Bulanan)
            $topKpiQueryBase = RekapBulanan::query()
                ->select(
                    'rekap_bulanans.nik',
                    'karyawan.nama_lengkap',
                    'rekap_bulanans.total_poin_kpi as total_points',
                    'karyawan.foto',
                    'jabatan.nama_jabatan as jabatan_nama',
                    'cabang.nama_cabang'
                )
                ->join('karyawan', 'rekap_bulanans.nik', '=', 'karyawan.nik')
                ->leftJoin('cabang', 'karyawan.kode_cabang', '=', 'cabang.kode_cabang')
                ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
                ->where('rekap_bulanans.bulan', $bulan)
                ->where('rekap_bulanans.tahun', $tahun)
                ->orderByDesc('rekap_bulanans.total_poin_kpi');

            $topKpiOffice = (clone $topKpiQueryBase)->whereNotIn('karyawan.kode_cabang', $this->siteBranches)->limit(10)->get();
            $topKpiSite = (clone $topKpiQueryBase)->whereIn('karyawan.kode_cabang', $this->siteBranches)->limit(10)->get();
        } else {
            $rawQuery = LeaderboardSnapshot::query()
                ->select('leaderboard_snapshots.nik', 'leaderboard_snapshots.nama_lengkap', DB::raw('SUM(leaderboard_snapshots.points) as total_points'), 'karyawan.foto', 'jabatan.nama_jabatan as jabatan_nama', 'cabang.nama_cabang', 'leaderboard_snapshots.kode_cabang')
                ->join('karyawan', 'leaderboard_snapshots.nik', '=', 'karyawan.nik')
                ->leftJoin('cabang', 'karyawan.kode_cabang', '=', 'cabang.kode_cabang')
                ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
                ->whereBetween('leaderboard_snapshots.date', [$startDate, $endDate])
                ->groupBy('leaderboard_snapshots.nik', 'leaderboard_snapshots.nama_lengkap', 'karyawan.foto', 'jabatan.nama_jabatan', 'cabang.nama_cabang', 'leaderboard_snapshots.kode_cabang')
                ->get();

            $sortedCollection = $this->applyLiveTieBreaker($rawQuery, $startDate, $endDate, 'total_points');

            $topOffice = $sortedCollection->whereNotIn('kode_cabang', $this->siteBranches)->take(10)->values();
            $topSite = $sortedCollection->whereIn('kode_cabang', $this->siteBranches)->take(10)->values();

            $rawKpiQuery = KpiLeaderboardSnapshot::query()
                ->select('kpi_leaderboard_snapshots.nik', 'kpi_leaderboard_snapshots.nama_lengkap', DB::raw('SUM(kpi_leaderboard_snapshots.points) as total_points'), 'karyawan.foto', 'jabatan.nama_jabatan as jabatan_nama', 'cabang.nama_cabang', 'kpi_leaderboard_snapshots.kode_cabang')
                ->join('karyawan', 'kpi_leaderboard_snapshots.nik', '=', 'karyawan.nik')
                ->leftJoin('cabang', 'karyawan.kode_cabang', '=', 'cabang.kode_cabang')
                ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
                ->whereBetween('kpi_leaderboard_snapshots.date', [$startDate, $endDate])
                ->groupBy('kpi_leaderboard_snapshots.nik', 'kpi_leaderboard_snapshots.nama_lengkap', 'karyawan.foto', 'jabatan.nama_jabatan', 'cabang.nama_cabang', 'kpi_leaderboard_snapshots.kode_cabang')
                ->orderByDesc('total_points')
                ->get();

            $topKpiOffice = $rawKpiQuery->whereNotIn('kode_cabang', $this->siteBranches)->take(10)->values();
            $topKpiSite = $rawKpiQuery->whereIn('kode_cabang', $this->siteBranches)->take(10)->values();
        }

        // 2. FASTEST (Harian)
        $fastestQueryBase = DB::table('presensi')
            ->join('karyawan', 'presensi.nik', '=', 'karyawan.nik')
            ->leftJoin('cabang', 'karyawan.kode_cabang', '=', 'cabang.kode_cabang')
            ->where('presensi.tgl_presensi', $hariini)->where('presensi.status', 'h')->where('presensi.jam_in', '!=', '00:00:00')
            ->select('karyawan.nama_lengkap', 'karyawan.kode_cabang', 'presensi.jam_in', 'cabang.nama_cabang')
            ->orderBy('presensi.jam_in', 'asc')->limit(5);

        $fastestOffice = (clone $fastestQueryBase)->whereNotIn('karyawan.kode_cabang', $this->siteBranches)->get();
        $fastestSite = (clone $fastestQueryBase)->whereIn('karyawan.kode_cabang', $this->siteBranches)->get();

        // 2b. LATE LIST (Harian)
        $lateQueryBase = DB::table('presensi')
            ->join('karyawan', 'presensi.nik', '=', 'karyawan.nik')
            ->leftJoin('cabang', 'karyawan.kode_cabang', '=', 'cabang.kode_cabang')
            ->leftJoin('jam_kerja', 'presensi.kode_jam_kerja', '=', 'jam_kerja.kode_jam_kerja')
            ->where('presensi.tgl_presensi', $hariini)
            ->where('presensi.status', 'h')
            ->where('presensi.jam_in', '!=', '00:00:00')
            ->whereNotNull('jam_kerja.jam_masuk')
            ->whereColumn('presensi.jam_in', '>', 'jam_kerja.jam_masuk')
            ->select(
                'karyawan.nama_lengkap',
                'karyawan.kode_cabang',
                'presensi.jam_in',
                'jam_kerja.jam_masuk',
                'cabang.nama_cabang',
                DB::raw('FLOOR(EXTRACT(EPOCH FROM (presensi.jam_in::time - jam_kerja.jam_masuk::time)) / 60) as menit_terlambat')
            )
            ->orderByDesc('menit_terlambat')
            ->limit(5);

        $lateOffice = (clone $lateQueryBase)->whereNotIn('karyawan.kode_cabang', $this->siteBranches)->get();
        $lateSite = (clone $lateQueryBase)->whereIn('karyawan.kode_cabang', $this->siteBranches)->get();

        // 3. ACTIVITY LISTS (Reuse Overview Logic)
        $activityLists = $this->getOverviewActivityLists($hariini);
        $dataIzinSakit = $activityLists['dataIzinSakit'];
        $dataLembur = $activityLists['dataLembur'];
        $dataDinasLuar = $activityLists['dataDinasLuar'];

        $dataBelumPresensi = DB::table('karyawan')
            ->leftJoin('cabang', 'karyawan.kode_cabang', '=', 'cabang.kode_cabang')
            ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
            ->leftJoin('presensi', function ($join) use ($hariini) {
                $join->on('karyawan.nik', '=', 'presensi.nik')->where('presensi.tgl_presensi', '=', $hariini);
            })
            ->where('karyawan.status_aktif', 'Aktif')->whereNull('presensi.nik')
            ->select('karyawan.nama_lengkap', 'karyawan.foto', 'jabatan.nama_jabatan as jabatan_nama', 'karyawan.no_hp', 'cabang.nama_cabang')
            ->orderBy('karyawan.nama_lengkap', 'asc')->get();

        $karyawanSP = $activityLists['karyawanSP'];

        $dataPresensi = DB::table('presensi')
            ->join('karyawan', 'presensi.nik', '=', 'karyawan.nik')
            ->leftJoin('cabang', 'karyawan.kode_cabang', '=', 'cabang.kode_cabang')
            ->where('presensi.tgl_presensi', $hariini)->where('presensi.status', 'h')
            ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
            ->select('karyawan.nama_lengkap', 'karyawan.foto', 'jabatan.nama_jabatan as jabatan_nama', 'cabang.nama_cabang', 'presensi.jam_in', 'presensi.jam_out')
            ->orderBy('presensi.jam_in', 'desc')->get();

        return compact(
            'topOffice',
            'topSite',
            'topKpiOffice',
            'topKpiSite',
            'fastestOffice',
            'fastestSite',
            'lateOffice',
            'lateSite',
            'dataIzinSakit',
            'dataLembur',
            'dataDinasLuar',
            'dataBelumPresensi',
            'karyawanSP',
            'dataPresensi'
        );
    }

    /**
     * Logic: Leaderboard per Cabang (OPTIMIZED - Mengatasi N+1 Query)
     */
    private function getBranchLeaderboards($hariini, $isMonthly = false, $startDate = null, $endDate = null, $limit = 5)
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
    private function getKpiBranchLeaderboards($hariini, $isMonthly = false, $startDate = null, $endDate = null, $limit = 5)
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
    private function applyLiveTieBreaker($collection, $startDate, $endDate, $pointsField = 'total_points')
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
