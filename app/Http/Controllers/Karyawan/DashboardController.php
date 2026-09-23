<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\DinasLuar;
use App\Models\HariLibur;
use App\Models\Izin;
use App\Models\Karyawan;
use App\Models\KpiLeaderboardSnapshot;
use App\Models\LeaderboardSnapshot;
use App\Models\Pengumuman;
use App\Models\Presensi;
use App\Models\RekapBulanan;
use App\Models\SuratPeringatan;
use App\Services\JadwalKerjaService;
use App\Support\PeriodeKerja;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(private JadwalKerjaService $jadwalKerja) {}

    public function index()
    {
        $hariini = date('Y-m-d');
        $bulanini = (int) date('m');
        $tahunini = date('Y');

        if (! Auth::guard('karyawan')->check()) {
            return redirect('/')->with('error', 'Silakan login terlebih dahulu.');
        }

        $karyawan = Auth::guard('karyawan')->user();
        $nik = $karyawan->nik;
        $kode_dept = $karyawan->kode_dept;
        $kode_cabang = $karyawan->kode_cabang;

        $userWithDept = Karyawan::with(['departemen', 'cabang'])
            ->where('nik', $nik)
            ->first();

        $presensihariini = Presensi::where('nik', $nik)
            ->where('tgl_presensi', $hariini)
            ->first();

        [$startDate, $endDate] = PeriodeKerja::dari($hariini)->range();

        $tanggalPulangCepatBulanIni = Izin::where('nik', $nik)
            ->where('status', 'p')
            ->where('status_approved', 1)
            ->whereBetween('tgl_izin_dari', [$startDate, $endDate])
            ->pluck('tgl_izin_dari')
            ->map(function ($date) {
                return date('Y-m-d', strtotime($date));
            })
            ->unique()
            ->values()
            ->toArray();

        $isPulangCepatHariIni = in_array($hariini, $tanggalPulangCepatBulanIni);

        $histori_presensi = Presensi::where('presensi.nik', $nik)
            ->whereBetween('tgl_presensi', [$startDate, $endDate])
            ->leftjoin('jam_kerja', 'presensi.kode_jam_kerja', '=', 'jam_kerja.kode_jam_kerja')
            ->leftJoin('dinas_luar', function ($join) use ($nik) {
                $join->on(DB::raw('DATE(presensi.tgl_presensi)'), '>=', DB::raw('DATE(dinas_luar.tgl_mulai)'))
                    ->on(DB::raw('DATE(presensi.tgl_presensi)'), '<=', DB::raw('DATE(dinas_luar.tgl_selesai)'))
                    ->where('dinas_luar.nik', '=', $nik)
                    ->where('dinas_luar.status_acc', '=', 'acc');
            })
            ->select('presensi.*', 'jam_kerja.nama_jam_kerja', 'jam_kerja.jam_masuk', 'jam_kerja.jam_pulang', 'dinas_luar.id as dinas_luar_id')
            ->orderBy('tgl_presensi', 'asc')
            ->get()
            ->map(function ($item) use ($tanggalPulangCepatBulanIni) {
                $tglPresensi = date('Y-m-d', strtotime($item->tgl_presensi));
                $item->is_pulang_cepat = ($item->status === 'h' && in_array($tglPresensi, $tanggalPulangCepatBulanIni));
                $payload = method_exists($item, 'toArray') ? $item->toArray() : (array) $item;

                return (object) $payload;
            });

        $histori_izin = Izin::with('masterCuti')
            ->where('nik', $nik)
            ->whereDate('tgl_izin_dari', '<=', $endDate)
            ->whereDate('tgl_izin_sampai', '>=', $startDate)
            ->where('status_approved', 1)
            ->get();

        $histori_dinas = DinasLuar::where('nik', $nik)
            ->where('status_acc', 'acc')
            ->whereDate('tgl_mulai', '<=', $endDate)
            ->whereDate('tgl_selesai', '>=', $startDate)
            ->get();

        $histori_normalized = collect();

        foreach ($histori_izin as $izin) {
            if ($izin->status === 'p') {
                continue;
            }
            try {
                $s = new \DateTime($izin->tgl_izin_dari);
                $e = new \DateTime($izin->tgl_izin_sampai);
                $e->modify('+1 day');
                $daterange = new \DatePeriod($s, new \DateInterval('P1D'), $e);
                foreach ($daterange as $date) {
                    $tgl = $date->format('Y-m-d');
                    if ($tgl >= $startDate && $tgl <= $endDate) {
                        $existsInPresensi = $histori_presensi->contains(function ($item) use ($tgl) {
                            return date('Y-m-d', strtotime($item->tgl_presensi)) === $tgl;
                        });
                        if (! $existsInPresensi) {
                            $histori_normalized->push((object) [
                                'tgl_presensi' => $tgl,
                                'jam_in' => '00:00:00',
                                'jam_out' => '00:00:00',
                                'status' => $izin->status,
                                'keterangan' => $izin->keterangan,
                                'nama_cuti' => $izin->masterCuti->nama_cuti ?? null,
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
            }
        }

        foreach ($histori_dinas as $dinas) {
            try {
                $s = new \DateTime($dinas->tgl_mulai);
                $e = new \DateTime($dinas->tgl_selesai);
                $e->modify('+1 day');
                $daterange = new \DatePeriod($s, new \DateInterval('P1D'), $e);
                foreach ($daterange as $date) {
                    $tgl = $date->format('Y-m-d');
                    if ($tgl >= $startDate && $tgl <= $endDate) {
                        $existsInPresensi = $histori_presensi->contains(function ($item) use ($tgl) {
                            return date('Y-m-d', strtotime($item->tgl_presensi)) === $tgl;
                        });
                        $existsInNormalized = $histori_normalized->contains('tgl_presensi', $tgl);
                        if (! $existsInPresensi && ! $existsInNormalized) {
                            $histori_normalized->push((object) [
                                'tgl_presensi' => $tgl,
                                'jam_in' => '00:00:00',
                                'jam_out' => '00:00:00',
                                'status' => 'd',
                                'keterangan' => $dinas->keterangan ?? $dinas->alasan ?? '-',
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
            }
        }

        $leaderboard_points = LeaderboardSnapshot::where('nik', $nik)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy(function ($item) {
                return Carbon::parse($item->date)->format('Y-m-d');
            });

        $tmtStr = $karyawan->tmt ? Carbon::parse($karyawan->tmt)->format('Y-m-d') : null;
        $startIterator = ($tmtStr && $tmtStr > $startDate) ? Carbon::parse($tmtStr) : Carbon::parse($startDate);
        $endIterator = Carbon::parse($endDate)->isFuture() ? Carbon::today() : Carbon::parse($endDate);

        // Hanya generate status Alpha jika karyawan TIDAK masuk whitelist
        if ((int) ($karyawan->is_whitelist ?? 0) !== 1) {
            while ($startIterator->lte($endIterator)) {
                $tgl = $startIterator->format('Y-m-d');
                $existsInPresensi = $histori_presensi->contains(function ($item) use ($tgl) {
                    return date('Y-m-d', strtotime($item->tgl_presensi)) === $tgl;
                });
                $existsInNormalized = $histori_normalized->contains('tgl_presensi', $tgl);

                if (! $existsInPresensi && ! $existsInNormalized) {
                    // Verifikasi Jam Kerja dan Hari Libur
                    $isHariLiburNasional = HariLibur::isHariLibur($tgl, $kode_cabang, $kode_dept);
                    $namaHari = $this->jadwalKerja->namaHari(date('D', strtotime($tgl)));
                    [$jkObj, $isLiburJamKerja, $source] = $this->jadwalKerja->untukHari($nik, $kode_dept, $kode_cabang, $namaHari);

                    // Logika Hari Libur yang lebih ketat
                    $isHoliday = $isHariLiburNasional || $isLiburJamKerja || ($namaHari == 'Minggu' && $source == 'none');

                    if (! $isHoliday) {
                        // Untuk shift lintas hari, tanggal sebelumnya bisa belum melewati jam pulang.
                        if (! empty($jkObj)) {
                            $jamPulangStr = $jkObj->jam_pulang;
                            $waktuSekarang = Carbon::now();
                            $waktuPulang = Carbon::parse($tgl.' '.$jamPulangStr);

                            if ($jkObj->lintashari == 1) {
                                $waktuPulang->addDay();
                            }

                            if ($waktuSekarang->lt($waktuPulang)) {
                                $startIterator->addDay();

                                continue;
                            }
                        }

                        $histori_normalized->push((object) [
                            'tgl_presensi' => $tgl,
                            'jam_in' => '00:00:00',
                            'jam_out' => '00:00:00',
                            'status' => 'a',
                            'keterangan' => 'Alpha / Mangkir',
                        ]);
                    }
                }
                $startIterator->addDay();
            }
        }

        $histori_presensi = $histori_presensi->map(function ($item) use ($leaderboard_points) {
            $tgl = date('Y-m-d', strtotime($item->tgl_presensi));
            $item->daily_points = $leaderboard_points->get($tgl)?->first();

            return $item;
        });

        $histori_normalized = $histori_normalized->map(function ($item) use ($leaderboard_points) {
            $item->daily_points = $leaderboard_points->get($item->tgl_presensi)?->first();

            return $item;
        });

        // Gabungkan dan Filter: Pastikan status 'Alpha' tidak ditampilkan jika diidentifikasi sebagai hari libur
        $historibulanini = collect($histori_presensi)->merge($histori_normalized)
            ->filter(function ($item) use ($nik, $kode_dept, $kode_cabang) {
                if ($item->status == 'a') {
                    $tgl = date('Y-m-d', strtotime($item->tgl_presensi));
                    $isHariLiburNasional = HariLibur::isHariLibur($tgl, $kode_cabang, $kode_dept);
                    $namaHari = $this->jadwalKerja->namaHari(date('D', strtotime($tgl)));
                    [$jkObj, $isLiburJamKerja, $source] = $this->jadwalKerja->untukHari($nik, $kode_dept, $kode_cabang, $namaHari);

                    if ($isHariLiburNasional || $isLiburJamKerja || ($namaHari == 'Minggu' && $source == 'none')) {
                        return false;
                    }
                    if (empty($jkObj)) {
                        return false;
                    }
                }

                return true;
            })
            ->sortBy('tgl_presensi')
            ->values();

        $rekappresensi = Presensi::query()
            ->where('presensi.nik', $nik)
            ->whereBetween('presensi.tgl_presensi', [$startDate, $endDate])
            ->leftJoin('jam_kerja', 'presensi.kode_jam_kerja', '=', 'jam_kerja.kode_jam_kerja')
            ->selectRaw("
                COUNT(CASE WHEN presensi.status = 'h' AND presensi.jam_in IS NOT NULL AND presensi.jam_in != '00:00:00' THEN 1 END) AS jmlhadir,
                SUM(CASE WHEN presensi.status = 'h' AND presensi.jam_in != '00:00:00' AND jam_kerja.jam_masuk IS NOT NULL AND presensi.jam_in::time > jam_kerja.jam_masuk::time THEN 1 ELSE 0 END) AS jmlterlambat
            ")
            ->first();

        $dateObj = Carbon::parse($endDate);
        $bulan = $dateObj->month;
        $tahun = $dateObj->year;

        $hasRekap = RekapBulanan::where('bulan', $bulan)->where('tahun', $tahun)->exists();
        $siteBranches = array_map('trim', explode(',', get_setting('cabang_tambang', 'CBNG0003,CBNG0011,RBJ,TBKR,CBNG0002')));
        $isTambang = in_array($karyawan->kode_cabang, $siteBranches);

        $userRank = null;

        if ($hasRekap) {
            $leaderboard = RekapBulanan::query()
                ->select(
                    'rekap_bulanans.nik',
                    'karyawan.nama_lengkap',
                    'rekap_bulanans.total_poin as total_points',
                    'karyawan.foto',
                    'jabatan.nama_jabatan as jabatan_nama'
                )
                ->join('karyawan', 'rekap_bulanans.nik', '=', 'karyawan.nik')
                ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
                ->where('rekap_bulanans.bulan', $bulan)
                ->where('rekap_bulanans.tahun', $tahun)
                ->orderByDesc('rekap_bulanans.total_poin')
                ->orderBy('rekap_bulanans.avg_jam_masuk', 'asc')
                ->limit(10)
                ->get();

            $userPoints = RekapBulanan::where('nik', $nik)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->value('total_poin') ?? 0;

            $allRekap = RekapBulanan::where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->where('kode_cabang', $kode_cabang)
                ->orderByDesc('total_poin')
                ->orderBy('avg_jam_masuk', 'asc')
                ->pluck('nik')
                ->toArray();

            $userRankIndex = array_search($nik, $allRekap);
            $userRank = $userRankIndex !== false ? $userRankIndex + 1 : null;
        } else {
            $rawCollection = LeaderboardSnapshot::query()
                ->select(
                    'leaderboard_snapshots.nik',
                    'leaderboard_snapshots.nama_lengkap',
                    DB::raw('SUM(leaderboard_snapshots.points) as total_points'),
                    'karyawan.foto',
                    'jabatan.nama_jabatan as jabatan_nama'
                )
                ->join('karyawan', 'leaderboard_snapshots.nik', '=', 'karyawan.nik')
                ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
                ->whereBetween('leaderboard_snapshots.date', [$startDate, $hariini])
                ->groupBy(
                    'leaderboard_snapshots.nik',
                    'leaderboard_snapshots.nama_lengkap',
                    'karyawan.foto',
                    'jabatan.nama_jabatan'
                )
                ->get();

            $fullLeaderboard = $this->applyLiveTieBreaker($rawCollection, $startDate, $hariini, 'total_points');
            $leaderboard = $fullLeaderboard->take(10);

            $userPoints = LeaderboardSnapshot::query()
                ->where('leaderboard_snapshots.nik', $nik)
                ->whereBetween('leaderboard_snapshots.date', [$startDate, $hariini])
                ->sum('leaderboard_snapshots.points');

            $rawBranchCollection = LeaderboardSnapshot::query()
                ->select(
                    'leaderboard_snapshots.nik',
                    'leaderboard_snapshots.nama_lengkap',
                    DB::raw('SUM(leaderboard_snapshots.points) as total_points'),
                    'karyawan.foto',
                    'jabatan.nama_jabatan as jabatan_nama'
                )
                ->join('karyawan', 'leaderboard_snapshots.nik', '=', 'karyawan.nik')
                ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
                ->whereBetween('leaderboard_snapshots.date', [$startDate, $hariini])
                ->where('karyawan.kode_cabang', $kode_cabang)
                ->groupBy(
                    'leaderboard_snapshots.nik',
                    'leaderboard_snapshots.nama_lengkap',
                    'karyawan.foto',
                    'jabatan.nama_jabatan'
                )
                ->get();

            $fullBranchLeaderboard = $this->applyLiveTieBreaker($rawBranchCollection, $startDate, $hariini, 'total_points');

            foreach ($fullBranchLeaderboard as $index => $item) {
                if ($item->nik === $nik) {
                    $userRank = $index + 1;
                    break;
                }
            }
        }

        $userPointsToday = LeaderboardSnapshot::query()
            ->where('leaderboard_snapshots.nik', $nik)
            ->where('leaderboard_snapshots.date', $hariini)
            ->sum('leaderboard_snapshots.points');

        if ($hasRekap) {
            $kpiLeaderboard = RekapBulanan::query()
                ->select(
                    'rekap_bulanans.nik',
                    'karyawan.nama_lengkap',
                    'rekap_bulanans.total_poin_kpi as total_points',
                    'karyawan.foto',
                    'jabatan.nama_jabatan as jabatan_nama'
                )
                ->join('karyawan', 'rekap_bulanans.nik', '=', 'karyawan.nik')
                ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
                ->where('rekap_bulanans.bulan', $bulan)
                ->where('rekap_bulanans.tahun', $tahun)
                ->orderByDesc('rekap_bulanans.total_poin_kpi')
                ->limit(10)
                ->get();

            $kpiUserPoints = RekapBulanan::where('nik', $nik)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->value('total_poin_kpi') ?? 0;
        } else {
            $kpiLeaderboard = KpiLeaderboardSnapshot::query()
                ->select(
                    'kpi_leaderboard_snapshots.nik',
                    'kpi_leaderboard_snapshots.nama_lengkap',
                    DB::raw('SUM(kpi_leaderboard_snapshots.points) as total_points'),
                    'karyawan.foto',
                    'jabatan.nama_jabatan as jabatan_nama'
                )
                ->join('karyawan', 'kpi_leaderboard_snapshots.nik', '=', 'karyawan.nik')
                ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
                ->whereBetween('kpi_leaderboard_snapshots.date', [$startDate, $hariini])
                ->groupBy(
                    'kpi_leaderboard_snapshots.nik',
                    'kpi_leaderboard_snapshots.nama_lengkap',
                    'karyawan.foto',
                    'jabatan.nama_jabatan'
                )
                ->orderByDesc('total_points')
                ->limit(10)
                ->get();

            $kpiUserPoints = KpiLeaderboardSnapshot::query()
                ->where('kpi_leaderboard_snapshots.nik', $nik)
                ->whereBetween('kpi_leaderboard_snapshots.date', [$startDate, $hariini])
                ->sum('kpi_leaderboard_snapshots.points');
        }

        $kpiUserPointsToday = KpiLeaderboardSnapshot::query()
            ->where('kpi_leaderboard_snapshots.nik', $nik)
            ->where('kpi_leaderboard_snapshots.date', $hariini)
            ->sum('kpi_leaderboard_snapshots.points');

        $rekapizin = Presensi::query()
            ->where('nik', $nik)
            ->whereBetween('tgl_presensi', [$startDate, $endDate])
            ->selectRaw("
                SUM(CASE WHEN status = 'i' THEN 1 ELSE 0 END) AS jmlizin,
                SUM(CASE WHEN status = 's' THEN 1 ELSE 0 END) AS jmlsakit,
                SUM(CASE WHEN status = 'c' THEN 1 ELSE 0 END) AS jmlcuti,
                SUM(CASE WHEN status = 'r' THEN 1 ELSE 0 END) AS jmlroster
            ")
            ->first();

        $namabulan = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $pengumuman = Pengumuman::aktif()->orderBy('tanggal_mulai', 'desc')->get();

        $hariiniStr = Carbon::today()->format('Y-m-d');
        $cekSP = SuratPeringatan::where('nik', $nik)
            ->whereDate('issued_at', '<=', $hariiniStr)
            ->whereDate('expires_at', '>=', $hariiniStr)
            ->orderBy('id', 'desc')
            ->first();

        // Cek Kelengkapan Data Profil
        $fieldsToCheck = [
            'nama_panggilan', 'no_hp', 'email', 'alamat', 'agama',
            'status_pernikahan', 'pendidikan_terakhir', 'no_rekening',
            'nama_darurat', 'no_darurat', 'hubungan_darurat',
            'no_bpjs_kesehatan', 'foto_bpjs_kesehatan',
        ];

        $missingFields = [];
        foreach ($fieldsToCheck as $field) {
            if (empty($karyawan->$field)) {
                $missingFields[] = $field;
            }
        }
        $isProfileIncomplete = ! empty($missingFields);

        $bonusDate = Carbon::parse($endDate)->subMonthsNoOverflow(1);
        $bonusBulan = $bonusDate->month;
        $bonusTahun = $bonusDate->year;
        $bonusPeriodText = $namabulan[$bonusBulan].' '.$bonusTahun;

        $rekapBulananUser = RekapBulanan::where('nik', $nik)
            ->where('bulan', $bonusBulan)
            ->where('tahun', $bonusTahun)
            ->first();

        return view('karyawan.dashboard.dashboard', compact(
            'presensihariini',
            'historibulanini',
            'rekappresensi',
            'leaderboard',
            'rekapizin',
            'namabulan',
            'bulanini',
            'tahunini',
            'userWithDept',
            'userPoints',
            'userPointsToday',
            'kpiLeaderboard',
            'kpiUserPoints',
            'kpiUserPointsToday',
            'pengumuman',
            'cekSP',
            'isPulangCepatHariIni',
            'isProfileIncomplete',
            'rekapBulananUser',
            'isTambang',
            'userRank',
            'bonusPeriodText'
        ));
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
