<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RegistrationToken;
use App\Services\Dashboard\AdminDashboardService;
use App\Services\Dashboard\CultureWidgetService;
use App\Services\Dashboard\LeaderboardService;
use App\Services\Dashboard\MonitoringDashboardService;
use App\Services\Dashboard\WorkforceStatsService;
use App\Support\PeriodeKerja;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Dashboard admin, overview, dan TV. Isi widget dihitung service di App\Services\Dashboard.
 */
class DashboardController extends Controller
{
    public function __construct(
        private AdminDashboardService $admin,
        private CultureWidgetService $culture,
        private WorkforceStatsService $workforce,
        private MonitoringDashboardService $monitoring,
        private LeaderboardService $leaderboard,
    ) {}

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
        $isAdminCabang = (bool) $user?->isAdminCabang();
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
        $statsRealtime = $this->admin->getRealtimeStats($ctx, $userCtx);
        $realtimeTrend = $this->admin->getRealtimeTrends($ctx, $userCtx);
        $kpiVisualStats = $this->admin->getKpiVisualStats($ctx, $userCtx);
        $statsDemografi = $this->workforce->getDemographics($userCtx);
        $statsTurnover = $this->workforce->getTurnoverStats($ctx, $userCtx, $statsRealtime['jmlkaryawan']);
        $listsData = $this->admin->getDataLists($ctx, $userCtx);
        $requestSummary = $this->admin->getRequestSummaryStats($userCtx);
        $globalFilters = $this->admin->getGlobalFilters($userCtx);
        $criticalNotifications = $this->admin->getCriticalNotifications($ctx, $userCtx, $statsRealtime);
        $selectedMonth = $request->query('bulan');
        $cultureWidgets = $this->culture->getCultureAndDailyWidgets($ctx, $userCtx, $selectedMonth);

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
        $headerStats = $this->monitoring->getDailyHeaderStats($hariini);

        // 3. List Data Operasional
        $lists = $this->monitoring->getOverviewLists($hariini, $periodInfo['startDate'], $periodInfo['endDate']);

        // 3b. Activity Lists (Izin/Sakit/Cuti, Dinas Luar, Lembur, SP)
        $activityLists = $this->monitoring->getOverviewActivityLists($hariini);

        // 4. Leaderboard per Cabang (OPTIMIZED: Eager Loading)
        // Kita gunakan $isMonthly = true dan false untuk mengambil dua jenis data
        $leaderboardsByCabang = $this->leaderboard->getBranchLeaderboards($hariini, false, $periodInfo['startDate'], $periodInfo['endDate'], null); // Harian
        $leaderboardsByCabangMonthly = $this->leaderboard->getBranchLeaderboards($hariini, true, $periodInfo['startDate'], $periodInfo['endDate'], null); // Bulanan

        // 5. KPI Leaderboard per Cabang
        $kpiLeaderboardsByCabang = $this->leaderboard->getKpiBranchLeaderboards($hariini, false, $periodInfo['startDate'], $periodInfo['endDate'], null); // Harian KPI
        $kpiLeaderboardsByCabangMonthly = $this->leaderboard->getKpiBranchLeaderboards($hariini, true, $periodInfo['startDate'], $periodInfo['endDate'], null); // Bulanan KPI

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
        $headerStats = $this->monitoring->getDailyHeaderStats($hariini);

        // 3. Data Middle Grid (Top Performance & Activity)
        $tvStats = $this->monitoring->getTvStats($hariini, $periodInfo['startDate'], $periodInfo['endDate']);

        // 4. Data Bottom Grid (Leaderboard Harian per Cabang) - OPTIMIZED
        $leaderboardsByCabang = $this->leaderboard->getBranchLeaderboards($hariini, false, $periodInfo['startDate'], $periodInfo['endDate'], null);
        $kpiLeaderboardsByCabang = $this->leaderboard->getKpiBranchLeaderboards($hariini, false, $periodInfo['startDate'], $periodInfo['endDate'], null);

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
}
