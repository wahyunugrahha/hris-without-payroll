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

    public function __construct(private RekapKehadiranHarian $rekapKehadiran) {}

    /**
     * Statistik kehadiran hari ini + pembanding hari yang sama minggu lalu.
     * Rumus ada di RekapKehadiranHarian (libur & karyawan non-wajib presensi tidak dihitung alpha).
     */
    public function getRealtimeStats($ctx, $userCtx)
    {
        $today = Carbon::parse($ctx['today']);
        $prevWeekDate = $today->copy()->subDays(7);

        $rekap = $this->rekapKehadiran->untuk([$today->toDateString(), $prevWeekDate->toDateString()], $userCtx);
        $hariIni = $rekap[$today->toDateString()];
        $mingguLalu = $rekap[$prevWeekDate->toDateString()];

        // Total karyawan aktif (termasuk yang dikecualikan dari presensi) untuk demografi & turnover.
        $queryKaryawan = Karyawan::where('status_aktif', Karyawan::STATUS_AKTIF);
        $this->applyCabangFilter($queryKaryawan, $userCtx);
        $jmlkaryawan = $queryKaryawan->count();

        $metrik = fn (array $r) => [
            'hadir' => $r['hadir'],
            'terlambat' => $r['terlambat'],
            'izin' => $r['izin'],
            'sakit' => $r['sakit'],
            'cuti' => $r['cuti'],
            'roster' => $r['roster'],
            'dinas_luar' => $r['dinas_luar'],
            'tanpa_keterangan' => $r['alpha'],
        ];
        $prevValues = $metrik($mingguLalu);

        $realtimeComparison = [];
        foreach ($metrik($hariIni) as $key => $currentValue) {
            $prevWeekValue = (float) $prevValues[$key];
            $delta = round($currentValue - $prevWeekValue, 1);

            $realtimeComparison[$key] = [
                'prev_week' => $prevWeekValue,
                'prev_week_date' => $prevWeekDate->format('Y-m-d'),
                'delta' => $delta,
                'delta_pct' => $prevWeekValue > 0 ? round(($delta / $prevWeekValue) * 100, 1) : null,
            ];
        }

        return [
            'jmlhadir' => $hariIni['hadir'],
            'jmlterlambat' => $hariIni['terlambat'],
            'jmlizin' => $hariIni['izin'],
            'jmlsakit' => $hariIni['sakit'],
            'jmlcuti' => $hariIni['cuti'],
            'jmlroster' => $hariIni['roster'],
            'jmlDinasLuar' => $hariIni['dinas_luar'],
            'jmlTanpaKeterangan' => $hariIni['alpha'],
            'jmltidakabsen' => $hariIni['alpha'],
            'jmlBelumAbsen' => $hariIni['belum_absen'],
            'jmlDijadwalkan' => $hariIni['dijadwalkan'],
            'jmlLibur' => $hariIni['libur'],
            'jmlkaryawan' => $jmlkaryawan,
            'realtimeComparison' => $realtimeComparison,
        ];
    }

    /**
     * Tren 7 hari terakhir (hadir, terlambat, izin/sakit/cuti/roster, alpha).
     */
    public function getRealtimeTrends($ctx, $userCtx)
    {
        $tanggal = collect(range(6, 0))
            ->map(fn ($i) => Carbon::parse($ctx['today'])->subDays($i)->toDateString())
            ->all();
        $rekap = collect($this->rekapKehadiran->untuk($tanggal, $userCtx));

        return [
            'trendHadir' => $rekap->pluck('hadir')->values()->all(),
            'trendTerlambat' => $rekap->pluck('terlambat')->values()->all(),
            'trendIzin' => $rekap->map(fn ($r) => $r['izin'] + $r['sakit'] + $r['cuti'] + $r['roster'])->values()->all(),
            'trendAlpha' => $rekap->pluck('alpha')->values()->all(),
        ];
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
