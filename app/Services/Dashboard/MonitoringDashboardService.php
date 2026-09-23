<?php

namespace App\Services\Dashboard;

use App\Models\Izin;
use App\Models\KpiLeaderboardSnapshot;
use App\Models\LeaderboardSnapshot;
use App\Models\Lembur;
use App\Models\Presensi;
use App\Models\RekapBulanan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Data dashboard overview & TV: statistik harian, daftar operasional, aktivitas.
 */
class MonitoringDashboardService
{
    public function __construct(private LeaderboardService $leaderboard) {}

    /**
     * Logic: Header Stats untuk Overview & TV
     */
    public function getDailyHeaderStats($date)
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
    public function getOverviewLists($hariini, $startDate, $endDate)
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

            $topOffice = (clone $topQueryBase)->whereNotIn('karyawan.kode_cabang', $this->leaderboard->siteBranches())->limit(10)->get();
            $topSite = (clone $topQueryBase)->whereIn('karyawan.kode_cabang', $this->leaderboard->siteBranches())->limit(10)->get();
        } else {
            $rawQuery = LeaderboardSnapshot::query()
                ->select('leaderboard_snapshots.nik', 'leaderboard_snapshots.nama_lengkap', DB::raw('SUM(leaderboard_snapshots.points) as total_points'), 'karyawan.foto', 'jabatan.nama_jabatan as jabatan_nama', 'cabang.nama_cabang', 'leaderboard_snapshots.kode_cabang')
                ->join('karyawan', 'leaderboard_snapshots.nik', '=', 'karyawan.nik')
                ->leftJoin('cabang', 'karyawan.kode_cabang', '=', 'cabang.kode_cabang')
                ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
                ->whereBetween('leaderboard_snapshots.date', [$startDate, $endDate])
                ->groupBy('leaderboard_snapshots.nik', 'leaderboard_snapshots.nama_lengkap', 'karyawan.foto', 'jabatan.nama_jabatan', 'cabang.nama_cabang', 'leaderboard_snapshots.kode_cabang')
                ->get();

            $sortedCollection = $this->leaderboard->applyLiveTieBreaker($rawQuery, $startDate, $endDate, 'total_points');

            $topOffice = $sortedCollection->whereNotIn('kode_cabang', $this->leaderboard->siteBranches())->take(10)->values();
            $topSite = $sortedCollection->whereIn('kode_cabang', $this->leaderboard->siteBranches())->take(10)->values();
        }

        return compact('dataPresensi', 'dataBelumPresensi', 'globalDaily', 'topOffice', 'topSite');
    }

    /**
     * Logic: Overview Activity Lists (Izin/Sakit/Cuti, Lembur, Dinas Luar, SP)
     */
    public function getOverviewActivityLists($hariini)
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
    public function getTvStats($hariini, $startDate, $endDate)
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

            $topOffice = (clone $topQueryBase)->whereNotIn('karyawan.kode_cabang', $this->leaderboard->siteBranches())->limit(10)->get();
            $topSite = (clone $topQueryBase)->whereIn('karyawan.kode_cabang', $this->leaderboard->siteBranches())->limit(10)->get();

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

            $topKpiOffice = (clone $topKpiQueryBase)->whereNotIn('karyawan.kode_cabang', $this->leaderboard->siteBranches())->limit(10)->get();
            $topKpiSite = (clone $topKpiQueryBase)->whereIn('karyawan.kode_cabang', $this->leaderboard->siteBranches())->limit(10)->get();
        } else {
            $rawQuery = LeaderboardSnapshot::query()
                ->select('leaderboard_snapshots.nik', 'leaderboard_snapshots.nama_lengkap', DB::raw('SUM(leaderboard_snapshots.points) as total_points'), 'karyawan.foto', 'jabatan.nama_jabatan as jabatan_nama', 'cabang.nama_cabang', 'leaderboard_snapshots.kode_cabang')
                ->join('karyawan', 'leaderboard_snapshots.nik', '=', 'karyawan.nik')
                ->leftJoin('cabang', 'karyawan.kode_cabang', '=', 'cabang.kode_cabang')
                ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
                ->whereBetween('leaderboard_snapshots.date', [$startDate, $endDate])
                ->groupBy('leaderboard_snapshots.nik', 'leaderboard_snapshots.nama_lengkap', 'karyawan.foto', 'jabatan.nama_jabatan', 'cabang.nama_cabang', 'leaderboard_snapshots.kode_cabang')
                ->get();

            $sortedCollection = $this->leaderboard->applyLiveTieBreaker($rawQuery, $startDate, $endDate, 'total_points');

            $topOffice = $sortedCollection->whereNotIn('kode_cabang', $this->leaderboard->siteBranches())->take(10)->values();
            $topSite = $sortedCollection->whereIn('kode_cabang', $this->leaderboard->siteBranches())->take(10)->values();

            $rawKpiQuery = KpiLeaderboardSnapshot::query()
                ->select('kpi_leaderboard_snapshots.nik', 'kpi_leaderboard_snapshots.nama_lengkap', DB::raw('SUM(kpi_leaderboard_snapshots.points) as total_points'), 'karyawan.foto', 'jabatan.nama_jabatan as jabatan_nama', 'cabang.nama_cabang', 'kpi_leaderboard_snapshots.kode_cabang')
                ->join('karyawan', 'kpi_leaderboard_snapshots.nik', '=', 'karyawan.nik')
                ->leftJoin('cabang', 'karyawan.kode_cabang', '=', 'cabang.kode_cabang')
                ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
                ->whereBetween('kpi_leaderboard_snapshots.date', [$startDate, $endDate])
                ->groupBy('kpi_leaderboard_snapshots.nik', 'kpi_leaderboard_snapshots.nama_lengkap', 'karyawan.foto', 'jabatan.nama_jabatan', 'cabang.nama_cabang', 'kpi_leaderboard_snapshots.kode_cabang')
                ->orderByDesc('total_points')
                ->get();

            $topKpiOffice = $rawKpiQuery->whereNotIn('kode_cabang', $this->leaderboard->siteBranches())->take(10)->values();
            $topKpiSite = $rawKpiQuery->whereIn('kode_cabang', $this->leaderboard->siteBranches())->take(10)->values();
        }

        // 2. FASTEST (Harian)
        $fastestQueryBase = DB::table('presensi')
            ->join('karyawan', 'presensi.nik', '=', 'karyawan.nik')
            ->leftJoin('cabang', 'karyawan.kode_cabang', '=', 'cabang.kode_cabang')
            ->where('presensi.tgl_presensi', $hariini)->where('presensi.status', 'h')->where('presensi.jam_in', '!=', '00:00:00')
            ->select('karyawan.nama_lengkap', 'karyawan.kode_cabang', 'presensi.jam_in', 'cabang.nama_cabang')
            ->orderBy('presensi.jam_in', 'asc')->limit(5);

        $fastestOffice = (clone $fastestQueryBase)->whereNotIn('karyawan.kode_cabang', $this->leaderboard->siteBranches())->get();
        $fastestSite = (clone $fastestQueryBase)->whereIn('karyawan.kode_cabang', $this->leaderboard->siteBranches())->get();

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

        $lateOffice = (clone $lateQueryBase)->whereNotIn('karyawan.kode_cabang', $this->leaderboard->siteBranches())->get();
        $lateSite = (clone $lateQueryBase)->whereIn('karyawan.kode_cabang', $this->leaderboard->siteBranches())->get();

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
}
