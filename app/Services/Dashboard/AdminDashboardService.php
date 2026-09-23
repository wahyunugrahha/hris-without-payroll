<?php

namespace App\Services\Dashboard;

use App\Models\BpjsRequest;
use App\Models\Cabang;
use App\Models\Departemen;
use App\Models\DinasLuar;
use App\Models\Izin;
use App\Models\Karyawan;
use App\Models\KPIDaily;
use App\Models\KpiLeaderboardSnapshot;
use App\Models\Lembur;
use App\Models\Presensi;
use App\Models\SalaryIncrease;
use App\Services\Dashboard\Concerns\FiltersByUserContext;
use App\Support\PeriodeKerja;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Widget utama dashboard admin: statistik realtime, tren, KPI, daftar, notifikasi.
 */
class AdminDashboardService
{
    use FiltersByUserContext;

    /**
     * Logic: Statistik Realtime Admin
     */
    public function getRealtimeStats($ctx, $userCtx)
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
    public function getRealtimeTrends($ctx, $userCtx)
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
    public function getKpiVisualStats($ctx, $userCtx)
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
    public function getGlobalFilters($userCtx)
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
    public function getCriticalNotifications($ctx, $userCtx, $statsRealtime)
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
     * Logic: List Data Admin (Modals)
     */
    public function getDataLists($ctx, $userCtx)
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
    public function getRequestSummaryStats($userCtx)
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
}
