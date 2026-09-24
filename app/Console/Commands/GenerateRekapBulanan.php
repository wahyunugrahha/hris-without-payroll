<?php

namespace App\Console\Commands;

use App\Models\Izin;
use App\Models\Karyawan;
use App\Models\KPIAtasanDaily;
use App\Models\KPIDaily;
use App\Models\KpiLeaderboardSnapshot;
use App\Models\KPIMaster;
use App\Models\KPIReport;
use App\Models\LeaderboardSnapshot;
use App\Models\Presensi;
use App\Models\RekapBulanan;
use App\Support\PeriodeKerja;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateRekapBulanan extends Command
{
    protected $signature = 'rekap:bulanan {--bulan=} {--tahun=} {--from-january}';

    protected $description = 'Generate rekap bulanan untuk pengajuan gaji & bonus';

    public function handle()
    {
        $tahun = $this->option('tahun') ?? date('Y');

        if ($this->option('from-january')) {
            $bulanSekarang = (int) date('m');
            $tahunSekarang = (int) date('Y');

            // Batas maksimal bulan yang diproses untuk tahun ini
            $limitBulan = ((int) $tahun === $tahunSekarang) ? $bulanSekarang : 12;

            $this->info("🚀 Memulai sinkronisasi rekap bulanan dari Januari s/d bulan sekarang untuk Tahun $tahun...");

            for ($m = 1; $m <= $limitBulan; $m++) {
                $this->generateForMonth($m, $tahun);
            }

            $this->info('✅ Semua proses rekap batch selesai!');

            return;
        }

        $bulan = $this->option('bulan') ?? date('m');
        $this->generateForMonth((int) $bulan, $tahun);
    }

    private function generateForMonth($bulan, $tahun)
    {
        $bulanPad = str_pad($bulan, 2, '0', STR_PAD_LEFT);
        $periode = PeriodeKerja::bulan($bulan, $tahun);
        $tglAwal = $periode->mulai->toMutable();
        $tglAkhir = $periode->selesai->toMutable();

        $tglAwalStr = $tglAwal->format('Y-m-d');
        $tglAkhirStr = $tglAkhir->format('Y-m-d');

        $this->info("🔄 Menjalankan rekap bulanan untuk periode $tglAwalStr s/d $tglAkhirStr (Bulan $bulanPad Tahun $tahun)...");

        // Get all employees TMTs to skip those who haven't joined yet
        $karyawanTmts = Karyawan::whereNotNull('tmt')->get()->pluck('tmt', 'nik')->map(fn ($date) => $date->format('Y-m-d'))->toArray();

        // 1. Dapatkan total poin per karyawan dari LeaderboardSnapshot
        $poinKaryawan = LeaderboardSnapshot::whereBetween('date', [$tglAwalStr, $tglAkhirStr])
            ->whereNotNull('nik')
            ->where('nik', '!=', '')
            ->select('nik', 'kode_cabang', DB::raw('SUM(points) as total_poin'))
            ->groupBy('nik', 'kode_cabang')
            ->get();

        // 1b. Dapatkan total poin KPI per karyawan dari KpiLeaderboardSnapshot
        $poinKpiKaryawan = KpiLeaderboardSnapshot::whereBetween('date', [$tglAwalStr, $tglAkhirStr])
            ->whereNotNull('nik')
            ->where('nik', '!=', '')
            ->select('nik', DB::raw('SUM(points) as total_poin_kpi'))
            ->groupBy('nik')
            ->pluck('total_poin_kpi', 'nik');

        // 2. Dapatkan total hari izin/sakit per karyawan yang approved
        $semuaIzin = Izin::where('status_approved', 1)
            ->where(function ($q) use ($tglAwalStr, $tglAkhirStr) {
                $q->whereBetween('tgl_izin_dari', [$tglAwalStr, $tglAkhirStr])
                    ->orWhereBetween('tgl_izin_sampai', [$tglAwalStr, $tglAkhirStr])
                    ->orWhere(function ($q2) use ($tglAwalStr, $tglAkhirStr) {
                        $q2->where('tgl_izin_dari', '<', $tglAwalStr)
                            ->where('tgl_izin_sampai', '>', $tglAkhirStr);
                    });
            })
            ->whereIn('status', ['i', 's'])
            ->get();

        $izinSakitKaryawan = [];
        $periodCheck = CarbonPeriod::create($tglAwal, $tglAkhir);

        foreach ($semuaIzin as $izin) {
            $nik = $izin->nik;
            if (! isset($izinSakitKaryawan[$nik])) {
                $izinSakitKaryawan[$nik] = 0;
            }

            $mulai = Carbon::parse($izin->tgl_izin_dari);
            $sampai = Carbon::parse($izin->tgl_izin_sampai);

            foreach ($periodCheck as $date) {
                if ($date->betweenIncluded($mulai, $sampai)) {
                    $izinSakitKaryawan[$nik]++;
                }
            }
        }

        // 2b. Hitung rata-rata jam masuk (avg_jam_masuk) untuk tie-breaker presensi
        $nikList = $poinKaryawan->pluck('nik')->unique()->toArray();

        $presensiKaryawan = Presensi::whereBetween('tgl_presensi', [$tglAwalStr, $tglAkhirStr])
            ->whereIn('nik', $nikList)
            ->where('status', 'h')
            ->whereNotNull('jam_in')
            ->where('jam_in', '!=', '00:00:00')
            ->select('nik', 'jam_in')
            ->get()
            ->groupBy('nik');

        $avgJamMasukKaryawan = [];
        foreach ($nikList as $nik) {
            $presensis = $presensiKaryawan->get($nik, collect());
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
            if ($count > 0) {
                $avgSeconds = round($totalSeconds / $count);
                $avgHours = floor($avgSeconds / 3600);
                $avgMinutes = floor(($avgSeconds % 3600) / 60);
                $avgSecs = $avgSeconds % 60;

                $avgJamMasukKaryawan[$nik] = str_pad($avgHours, 2, '0', STR_PAD_LEFT).':'.
                                             str_pad($avgMinutes, 2, '0', STR_PAD_LEFT).':'.
                                             str_pad($avgSecs, 2, '0', STR_PAD_LEFT);
            } else {
                $avgJamMasukKaryawan[$nik] = '23:59:59'; // default lambat sekali untuk tie-breaker
            }
        }

        // Tambahkan avg_jam_masuk ke objek poinKaryawan agar bisa di-sorting
        foreach ($poinKaryawan as $pk) {
            $pk->avg_jam_masuk = $avgJamMasukKaryawan[$pk->nik] ?? '23:59:59';
        }

        // 3. Kelompokkan poin berdasarkan kode_cabang untuk menentukan Top 1,2,3
        $poinPerCabang = $poinKaryawan->groupBy('kode_cabang');

        foreach ($poinPerCabang as $kodeCabang => $karyawans) {
            // Urutkan dari poin tertinggi ke terendah, lalu avg_jam_masuk asc sebagai tie-breaker
            $sortedKaryawans = $karyawans->sortBy([
                ['total_poin', 'desc'],
                ['avg_jam_masuk', 'asc'],
            ])->values();

            foreach ($sortedKaryawans as $index => $karyawan) {
                // Skip jika belum join pada periode ini
                $tmtDate = $karyawanTmts[$karyawan->nik] ?? null;
                if ($tmtDate && $tmtDate > $tglAkhirStr) {
                    continue;
                }

                // Skip if record already exists when running from-january YTD to optimize performance
                if ($this->option('from-january')) {
                    $exists = RekapBulanan::where('nik', $karyawan->nik)
                        ->where('bulan', $bulan)
                        ->where('tahun', $tahun)
                        ->exists();
                    if ($exists) {
                        continue;
                    }
                }

                $bonus = 0;

                // Top 1, 2, 3 per cabang
                if ($index === 0) {
                    $bonus = 150000;
                } elseif ($index === 1) {
                    $bonus = 100000;
                } elseif ($index === 2) {
                    $bonus = 50000;
                }

                $totalIzinSakit = $izinSakitKaryawan[$karyawan->nik] ?? 0;
                $totalPoinKpi = $poinKpiKaryawan[$karyawan->nik] ?? 0;

                RekapBulanan::updateOrCreate(
                    [
                        'nik' => $karyawan->nik,
                        'bulan' => $bulan,
                        'tahun' => $tahun,
                    ],
                    [
                        'kode_cabang' => $kodeCabang,
                        'total_poin' => $karyawan->total_poin,
                        'total_poin_kpi' => $totalPoinKpi,
                        'total_izin_sakit' => $totalIzinSakit,
                        'bonus_bulanan' => $bonus,
                        'avg_jam_masuk' => $avgJamMasukKaryawan[$karyawan->nik] === '23:59:59' ? null : $avgJamMasukKaryawan[$karyawan->nik],
                    ]
                );
            }
        }

        $this->info('🔄 Menghitung & meng-generate hasil KPI bulanan untuk periode ini...');
        $this->generateKpiReportForMonth($bulan, $tahun);

        $this->info("✨ Rekap bulanan & laporan KPI untuk Bulan $bulanPad Tahun $tahun berhasil di-generate!");
    }

    private function generateKpiReportForMonth($bulan, $tahun)
    {
        $bulanPad = str_pad($bulan, 2, '0', STR_PAD_LEFT);
        $periode = PeriodeKerja::bulan($bulan, $tahun);
        $tglAwal = $periode->mulai->toMutable();
        $tglAkhir = $periode->selesai->toMutable();

        $tglAwalStr = $tglAwal->format('Y-m-d');
        $tglAkhirStr = $tglAkhir->format('Y-m-d');

        $karyawans = Karyawan::with(['jabatanRel', 'departemen', 'cabang'])->get();
        $nikList = $karyawans->pluck('nik')->toArray();

        // 1. Ambil Data Presensi & Agregasi Langsung di SQL
        $presensiStats = DB::table('presensi')
            ->select('nik',
                DB::raw('COUNT(id) as total_hari'),
                DB::raw('SUM(CASE WHEN jam_in <= \'09:10:00\' THEN 1 ELSE 0 END) as tepat_waktu')
            )
            ->whereIn('nik', $nikList)
            ->whereBetween('tgl_presensi', [$tglAwalStr, $tglAkhirStr])
            ->groupBy('nik')
            ->get()
            ->keyBy('nik');

        // 2. Ambil KPI Harian (Workbook & Extra)
        // Draft & KPI yang ditolak tidak dihitung (aturan sama dengan leaderboard).
        $kpiDailies = KPIDaily::with(['kpiDailyDetail', 'kpiDailyExtra'])
            ->dihitung()
            ->whereIn('nik', $nikList)
            ->whereBetween('tanggal', [$tglAwalStr, $tglAkhirStr])
            ->get()
            ->groupBy('nik');

        // 3. Ambil KPI Atasan
        $kpiAtasanDailies = KPIAtasanDaily::with('details')
            ->whereIn('nik', $nikList)
            ->whereBetween('tanggal', [$tglAwalStr, $tglAkhirStr])
            ->orderBy('tanggal', 'desc')
            ->get()
            ->groupBy('nik');

        // 4. Ambil Semua Template Master KPI yang aktif
        $allMasterKPIs = KPIMaster::with(['kpiMasterAtasan' => function ($q) {
            $q->where('is_active', 1);
        }])
            ->where('is_active', 1)
            ->get();

        foreach ($karyawans as $karyawan) {
            // Skip jika belum join pada periode ini
            if ($karyawan->tmt && $karyawan->tmt->format('Y-m-d') > $tglAkhirStr) {
                continue;
            }

            // Cari Template Master KPI yang sesuai (Berdasarkan Jabatan & Dept & Cabang dengan fallback)
            $kpiMaster = $allMasterKPIs
                ->where('jabatan_id', $karyawan->jabatan_id)
                ->where('kode_dept', $karyawan->kode_dept)
                ->where('kode_cabang', $karyawan->kode_cabang)
                ->first();

            if (! $kpiMaster) {
                $kpiMaster = $allMasterKPIs
                    ->where('jabatan_id', $karyawan->jabatan_id)
                    ->where('kode_dept', $karyawan->kode_dept)
                    ->whereNull('kode_cabang')
                    ->first();
            }

            if (! $kpiMaster) {
                $kpiMaster = $allMasterKPIs
                    ->where('jabatan_id', $karyawan->jabatan_id)
                    ->whereNull('kode_dept')
                    ->whereNull('kode_cabang')
                    ->first();
            }

            if (! $kpiMaster) {
                continue; // Skip jika Karyawan tidak punya format KPI
            }

            // A. HITUNG ABSENSI
            $bobotAtasanTotal = $kpiMaster->kpiMasterAtasan->sum('bobot_atasan');
            $bobotAbsensi = 100 - ($kpiMaster->bobot_kpi + $bobotAtasanTotal);

            $pStat = $presensiStats->get($karyawan->nik);
            $achieveAbsensi = ($pStat && $pStat->total_hari > 0)
                ? round(($pStat->tepat_waktu / $pStat->total_hari) * 100)
                : 0;

            $scoreAbsensi = min(100, $achieveAbsensi);
            $totalScoreAbsensi = round(($bobotAbsensi * $scoreAbsensi) / 100);

            // B. HITUNG KPI KARYAWAN (WORKBOOK + EXTRA)
            $karyawanDailies = $kpiDailies->get($karyawan->nik, collect());
            $achieveKaryawan = 0;

            if ($karyawanDailies->isNotEmpty()) {
                $totalScoreHarian = 0;
                foreach ($karyawanDailies as $daily) {
                    $scoreUtama = $daily->kpiDailyDetail->sum('score');
                    $scoreExtra = $daily->kpiDailyExtra->sum('score');

                    $persenHariIni = $kpiMaster->bobot_kpi > 0 ? (($scoreUtama + $scoreExtra) / $kpiMaster->bobot_kpi) * 100 : 0;
                    if ($persenHariIni > 100) {
                        $persenHariIni = 100;
                    }

                    $totalScoreHarian += $persenHariIni;
                }
                $achieveKaryawan = round($totalScoreHarian / $karyawanDailies->count());
            }

            $scoreKaryawan = min(100, $achieveKaryawan);
            $totalScoreKaryawan = round(($kpiMaster->bobot_kpi * $scoreKaryawan) / 100);

            // C. HITUNG PENILAIAN ATASAN
            $karyawanAtasanDailies = $kpiAtasanDailies->get($karyawan->nik, collect());
            $latestAtasan = $karyawanAtasanDailies->first();

            $atasanScoreSum = 0;
            foreach ($kpiMaster->kpiMasterAtasan as $atasanMaster) {
                $achieveAtasan = 0;
                if ($latestAtasan && $latestAtasan->details) {
                    $det = $latestAtasan->details->where('kpi_master_atasan_id', $atasanMaster->id)->first();
                    if ($det) {
                        $achieveAtasan = $det->score;
                    }
                }

                $scoreAtasan = min(100, $achieveAtasan);
                $totScoreA = round(($atasanMaster->bobot_atasan * $scoreAtasan) / 100);
                $atasanScoreSum += $totScoreA;
            }

            $overallScore = $totalScoreAbsensi + $totalScoreKaryawan + $atasanScoreSum;

            // D. SIMPAN HASIL FINAL KE TABEL kpi_report
            KPIReport::updateOrInsert(
                [
                    'nik' => $karyawan->nik,
                    'periode_bulan' => $bulanPad,
                    'periode_tahun' => $tahun,
                ],
                [
                    'kode_master' => $kpiMaster->kode_master,
                    'score_presensi' => $totalScoreAbsensi,
                    'score_workbook' => $totalScoreKaryawan,
                    'score_atasan' => $atasanScoreSum,
                    'final_score' => $overallScore,
                ]
            );
        }
    }
}
