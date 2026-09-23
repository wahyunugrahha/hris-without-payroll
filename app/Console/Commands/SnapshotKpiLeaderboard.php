<?php

namespace App\Console\Commands;

use App\Models\Cabang;
use App\Models\KPIDaily;
use App\Models\KpiLeaderboardSnapshot;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SnapshotKpiLeaderboard extends Command
{
    protected $signature = 'leaderboard:snapshot-kpi';

    protected $description = 'Simpan snapshot poin KPI harian untuk semua karyawan per cabang';

    public function handle()
    {
        $hariini = date('Y-m-d');
        $hariIniCarbon = Carbon::parse($hariini);

        // Penentuan Awal Siklus: Tanggal 26
        if ($hariIniCarbon->day >= 26) {
            $tglAwal = Carbon::create($hariIniCarbon->year, $hariIniCarbon->month, 26, 0, 0, 0);
            $tglAkhir = $tglAwal->copy()->addMonthsNoOverflow(1)->day(25)->format('Y-m-d');
        } else {
            $tglAkhirCarbon = Carbon::create($hariIniCarbon->year, $hariIniCarbon->month, 25, 0, 0, 0);
            $tglAwal = $tglAkhirCarbon->copy()->subMonthsNoOverflow(1)->day(26);
            $tglAkhir = $tglAkhirCarbon->format('Y-m-d');
        }

        $datesToProcess = [];
        for ($date = $tglAwal->copy(); $date->lte($hariIniCarbon); $date->addDay()) {
            $datesToProcess[] = $date->format('Y-m-d');
        }

        $this->info('🚀 Memulai proses snapshot KPI Leaderboard (Siklus Berjalan): '.$tglAwal->format('Y-m-d').' s/d '.$hariini);

        $cabangs = Cabang::all();

        // OPTIMASI: Chunking per tanggal untuk hemat memory (bukan all sekaligus)
        // Untuk data volume besar, ini lebih aman daripada load seluruh periode

        foreach ($datesToProcess as $processDate) {
            $this->info('>> Memproses Tanggal: '.$processDate);

            KpiLeaderboardSnapshot::where('date', $processDate)->delete();
            $insertData = [];

            // OPTIMASI: Query hanya untuk tanggal ini dengan eager loading
            $dailyKpiData = KPIDaily::with(['kpiDailyDetail', 'kpiDailyExtra', 'karyawan'])
                ->where('tanggal', $processDate)
                ->get()
                ->groupBy(function ($item) {
                    return $item->karyawan ? $item->karyawan->kode_cabang : 'UNKNOWN';
                });

            foreach ($cabangs as $cabang) {
                // Ambil data KPI dari hasil query hari ini
                $kpiDailies = $dailyKpiData->get($cabang->kode_cabang, collect());

                $calculatedData = [];

                foreach ($kpiDailies as $kpiRow) {
                    $kpiPoints = 0;

                    if ($kpiRow->kpiDailyDetail) {
                        $kpiPoints += $kpiRow->kpiDailyDetail->sum('score');
                    }
                    if ($kpiRow->kpiDailyExtra) {
                        $kpiPoints += $kpiRow->kpiDailyExtra->sum('score');
                    }

                    $nik = $kpiRow->nik;
                    $nama_lengkap = $kpiRow->karyawan ? $kpiRow->karyawan->nama_lengkap : 'Unknown';

                    $calculatedData[] = [
                        'nik' => $nik,
                        'nama_lengkap' => $nama_lengkap,
                        'points' => (int) round($kpiPoints),
                    ];
                }

                if (empty($calculatedData)) {
                    continue;
                }

                usort($calculatedData, function ($a, $b) {
                    if ($a['points'] === $b['points']) {
                        return strcmp((string) $a['nik'], (string) $b['nik']);
                    }

                    return $b['points'] <=> $a['points'];
                });

                $rank = 1;
                $now = now();
                foreach ($calculatedData as $data) {
                    $insertData[] = [
                        'date' => $processDate,
                        'kode_cabang' => $cabang->kode_cabang,
                        'rank' => $rank,
                        'points' => $data['points'],
                        'nik' => $data['nik'],
                        'nama_lengkap' => $data['nama_lengkap'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $rank++;
                }
            }

            // Batch insert per hari
            if (! empty($insertData)) {
                KpiLeaderboardSnapshot::insert($insertData);
            }
        }

        $this->info('✅ SELESAI. Semua poin KPI (Retroaktif) telah disinkronkan ke Leaderboard.');
    }
}
