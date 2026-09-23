<?php

namespace App\Http\Controllers\Admin\Kpi;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\Departemen;
use App\Models\HariLibur;
use App\Models\Izin;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\KPIAtasanDaily;
use App\Models\KPIDaily;
use App\Models\KPIMaster;
use App\Models\KPIMasterDetail;
use App\Models\KPIReport;
use App\Support\PeriodeKerja;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Rekap & report KPI karyawan per periode (tampilan dan cetak).
 */
class LaporanKpiController extends Controller
{
    public function rekapKPIKaryawan(Request $request)
    {
        $periodeIni = PeriodeKerja::dari();
        $defaultBulan = $periodeIni->bulanKe();
        $defaultTahun = $periodeIni->tahun();
        $bulan = $request->input('bulan', $defaultBulan);
        $tahun = $request->input('tahun', $defaultTahun);
        $kode_dept = $request->input('kode_dept');
        $kode_cabang = $request->input('kode_cabang');

        $user = Auth::guard('user')->user();
        if ($user?->isAdminCabang()) {
            $kode_cabang = $user->kode_cabang;
        }

        $namabulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $periodeList = [];
        for ($i = 1; $i <= 12; $i++) {
            $bulan_lalu = ($i == 1) ? 12 : $i - 1;

            $nama_bln_lalu = $namabulan[$bulan_lalu];
            $nama_bln_ini = $namabulan[$i];

            $periodeList[$i] = "26 $nama_bln_lalu - 25 $nama_bln_ini";
        }
        [$tglAwal, $tglAkhir] = PeriodeKerja::bulan($bulan, $tahun)->range();
        $nik_pencarian = $request->input('nik_pencarian');

        $karyawanQuery = Karyawan::with(['departemen', 'cabang'])
            ->when($kode_cabang, fn ($q) => $q->where('kode_cabang', $kode_cabang))
            ->when($kode_dept, fn ($q) => $q->where('kode_dept', $kode_dept))
            ->when($nik_pencarian, fn ($q) => $q->where('nik', $nik_pencarian));

        $listKaryawan = $karyawanQuery->orderBy('nama_lengkap')->paginate(50)->withQueryString();
        $niks = $listKaryawan->pluck('nik');

        $kpiBulanan = KPIDaily::whereBetween('tanggal', [$tglAwal, $tglAkhir])
            ->whereIn('nik', $niks)
            ->get()
            ->groupBy('nik');

        $penilaianAtasanData = KPIAtasanDaily::whereBetween('tanggal', [$tglAwal, $tglAkhir])
            ->whereIn('nik', $niks)
            ->get()
            ->keyBy('nik');

        $dataApproval = [];
        foreach ($listKaryawan as $karyawan) {
            $kpis = $kpiBulanan->get($karyawan->nik, collect());

            $totalLaporan = $kpis->count();
            $totalApprovedAtasan = $kpis->whereNotNull('approve_atasan')->count();
            $totalApprovedHR = $kpis->whereNotNull('approve_hr')->count();

            $penilaianAtasan = $penilaianAtasanData->get($karyawan->nik);
            $statusPenilaianAtasan = $penilaianAtasan ? $penilaianAtasan->status : 'Belum Dibuat';

            $dataApproval[] = (object) [
                'nik' => $karyawan->nik,
                'nama_lengkap' => $karyawan->nama_lengkap,
                'departemen' => $karyawan->departemen->nama_dept ?? '-',
                'cabang' => $karyawan->cabang->nama_cabang ?? '-',
                'total_laporan' => $totalLaporan,
                'total_approved_atasan' => $totalApprovedAtasan,
                'total_approved_hr' => $totalApprovedHR,
                'status_penilaian_atasan' => $statusPenilaianAtasan,
            ];
        }

        return view('admin.kpi.rekapkpi_karyawan', [
            'namabulan' => $namabulan,
            'periodeList' => $periodeList,
            'cabang' => Cabang::all(),
            'departemen' => Departemen::all(),
            'karyawan' => Karyawan::orderBy('nama_lengkap')->get(),
            'bulan_terpilih' => $bulan,
            'tahun_terpilih' => $tahun,
            'kode_dept_terpilih' => $kode_dept,
            'kode_cabang_terpilih' => $kode_cabang,
            'nik_pencarian' => $nik_pencarian,
            'dataApproval' => collect($dataApproval),
            'paginator' => $listKaryawan,
            'tglAwal' => $tglAwal,
            'tglAkhir' => $tglAkhir,
        ]);
    }

    public function cetakRekapKPIKaryawan(Request $request)
    {
        $bulan = $request->bulan;
        $tahun = $request->tahun;
        $kode_dept = $request->kode_dept;
        $kode_cabang = $request->kode_cabang;
        $niks = $request->input('niks', []);

        $user = Auth::guard('user')->user();
        if ($user?->isAdminCabang()) {
            $kode_cabang = $user->kode_cabang;
        }

        $namabulan = [
            '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
            'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];

        $karyawanQuery = Karyawan::with(['jabatanRel', 'departemen', 'cabang'])
            ->when($kode_cabang, fn ($q) => $q->where('kode_cabang', $kode_cabang))
            ->when($kode_dept, fn ($q) => $q->where('kode_dept', $kode_dept))
            ->when(! empty($niks), fn ($q) => $q->whereIn('nik', $niks));

        $karyawans = $karyawanQuery->orderBy('nama_lengkap')->get();
        $nikList = $karyawans->pluck('nik');

        [$tglAwal, $tglAkhir] = PeriodeKerja::bulan($bulan, $tahun)->range();
        $strAwal = Carbon::parse($tglAwal)->translatedFormat('d F Y');
        $strAkhir = Carbon::parse($tglAkhir)->translatedFormat('d F Y');
        $periodeString = "$strAwal - $strAkhir";

        $kpiData = KPIDaily::with(['kpiDailyDetail.kpiMasterDetail'])
            ->whereBetween('tanggal', [$tglAwal, $tglAkhir])
            ->where('status', 'approved_by_hr')
            ->whereIn('nik', $nikList)
            ->get();

        $rekap = [];
        foreach ($karyawans as $karyawan) {
            $karyawanKPIs = $kpiData->where('nik', $karyawan->nik);
            if ($karyawanKPIs->isEmpty()) {
                continue;
            }

            $totalHariKerjaEfektif = $this->getHariKerjaEfektif($tglAwal, $tglAkhir, $karyawan->nik);

            $masterIndikators = KPIMasterDetail::whereHas('kpiMaster', function ($q) use ($karyawan) {
                $q->where('jabatan_id', $karyawan->jabatan_id)
                    ->where('kode_dept', $karyawan->kode_dept);
            })
                ->where('is_active', true)
                ->orderBy('id')
                ->get();

            $items = [];
            $grandTotalScore = 0;
            $grandTotalProgress = 0;
            $grandTotalHasil = 0;

            foreach ($masterIndikators as $mi) {
                $progressCount = 0;

                foreach ($karyawanKPIs as $kpi) {
                    $detail = $kpi->kpiDailyDetail->where('kpi_master_detail_id', $mi->id)->first();
                    if ($detail && $detail->is_checked == 1) {
                        $progressCount++;
                    }
                }

                $hasilKpi = $totalHariKerjaEfektif > 0 ? ($progressCount / $totalHariKerjaEfektif) * $mi->score_indikator : 0;

                if ($hasilKpi > $mi->score_indikator) {
                    $hasilKpi = $mi->score_indikator;
                }

                $items[] = (object) [
                    'indikator' => floatval($mi->score_indikator).'%',
                    'description' => $mi->indikator,
                    'progress' => $progressCount,
                    'hasil' => round($hasilKpi, 2),
                ];

                $grandTotalScore += $mi->score_indikator;
                $grandTotalProgress += $progressCount;
                $grandTotalHasil += $hasilKpi;
            }

            $extras = [];
            $totalScoreExtra = 0;
            foreach ($karyawanKPIs as $kpi) {
                foreach ($kpi->kpiDailyExtra as $ext) {
                    $extras[] = (object) [
                        'tanggal' => date('d F Y', strtotime($kpi->tanggal)),
                        'kegiatan' => $ext->indikator_tambahan,
                        'catatan' => $ext->catatan ?? '-',
                        'score' => floatval($ext->score),
                    ];
                    $totalScoreExtra += $ext->score;
                }
            }

            $avgProgress = ($masterIndikators->count() > 0 && $totalHariKerjaEfektif > 0)
                            ? ($grandTotalProgress / ($totalHariKerjaEfektif * $masterIndikators->count())) * 100
                            : 0;

            if ($avgProgress > 100) {
                $avgProgress = 100;
            }

            $totalKeseluruhan = round($avgProgress, 1) + $totalScoreExtra;

            $rekap[] = (object) [
                'nik' => $karyawan->nik,
                'nama_lengkap' => $karyawan->nama_lengkap,
                'jabatan' => $karyawan->jabatanRel->nama_jabatan ?? '-',
                'departemen' => $karyawan->departemen->nama_dept ?? '-',
                'cabang' => $karyawan->cabang->nama_cabang ?? '-',
                'periode' => $periodeString,
                'hari_efektif' => $totalHariKerjaEfektif,
                'items' => $items,
                'extras' => $extras,
                'summary' => (object) [
                    'total_bobot' => floatval($grandTotalScore).'%',
                    'total_progress' => round($avgProgress, 1),
                    'total_hasil' => round($grandTotalHasil, 0),
                    'total_keseluruhan' => $totalKeseluruhan,
                ],
            ];
        }

        $data = compact('bulan', 'tahun', 'namabulan', 'rekap', 'tglAwal', 'tglAkhir');

        if ($request->exportexcel == '1') {
            $time = date('d-m-Y_His');

            return response()
                ->view('admin.kpi.cetakrekapkpiexcel_karyawan', $data)
                ->header('Content-Type', 'application/vnd.ms-excel')
                ->header('Content-Disposition', "attachment; filename=Rekap_KPI_Harian_{$namabulan[(int) $bulan]}_{$tahun}.xls");
        }

        return view('admin.kpi.cetakrekapkpi_karyawan', $data);
    }

    public function reportKPI()
    {
        $namabulan = [
            '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];

        $periodeList = [];
        for ($i = 1; $i <= 12; $i++) {
            $bulan_lalu = ($i == 1) ? 12 : $i - 1;
            $periodeList[$i] = '26 '.$namabulan[$bulan_lalu].' - 25 '.$namabulan[$i];
        }

        $periodeIni = PeriodeKerja::dari();
        $defaultBulan = $periodeIni->bulanKe();
        $defaultTahun = $periodeIni->tahun();

        $jabatan = Jabatan::orderBy('nama_jabatan')->get();
        $departemen = Departemen::orderBy('nama_dept')->get();
        $cabang = Cabang::orderBy('nama_cabang')->get();
        $karyawan = Karyawan::orderBy('nama_lengkap')->get();

        return view('admin.kpi.reportkpi', compact(
            'namabulan', 'periodeList', 'defaultBulan', 'defaultTahun',
            'jabatan', 'departemen', 'cabang', 'karyawan'
        ));
    }

    public function cetakReportKPI(Request $request)
    {
        $bulan = $request->bulan;
        $tahun = $request->tahun;
        $kode_dept = $request->kode_dept;
        $kode_cabang = $request->kode_cabang;
        $nik_input = $request->nik;

        $namabulan = [
            '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];

        [$tglAwal, $tglAkhir] = PeriodeKerja::bulan($bulan, $tahun)->range();
        $periodeString = $namabulan[(int) $bulan].' '.$tahun;

        // 1. FILTER KARYAWAN
        $karyawanQuery = Karyawan::with(['jabatanRel', 'departemen', 'cabang'])
            ->when($kode_cabang, fn ($q) => $q->where('kode_cabang', $kode_cabang))
            ->when($kode_dept, fn ($q) => $q->where('kode_dept', $kode_dept))
            ->when($nik_input, fn ($q) => $q->where('nik', $nik_input));

        $karyawans = $karyawanQuery->orderBy('nama_lengkap')->get();
        $nikList = $karyawans->pluck('nik')->toArray();

        if (empty($nikList)) {
            return back()->with('error', 'Tidak ada data karyawan pada filter yang dipilih.');
        }

        // =========================================================================
        // PRE-FETCHING DATA (Menghindari Query Database di dalam Looping Karyawan)
        // =========================================================================

        // A. Ambil Data Presensi & Agregasi Langsung di SQL
        $presensiStats = DB::table('presensi')
            ->select('nik',
                DB::raw('COUNT(id) as total_hari'),
                DB::raw('SUM(CASE WHEN jam_in <= \'09:10:00\' THEN 1 ELSE 0 END) as tepat_waktu')
            )
            ->whereIn('nik', $nikList)
            ->whereBetween('tgl_presensi', [$tglAwal, $tglAkhir])
            ->groupBy('nik')
            ->get()
            ->keyBy('nik');

        // B. Ambil KPI Harian (Workbook & Extra)
        $kpiDailies = KPIDaily::with(['kpiDailyDetail', 'kpiDailyExtra'])
            ->whereIn('nik', $nikList)
            ->whereBetween('tanggal', [$tglAwal, $tglAkhir])
            // ->where('status', 'approved_by_hr')
            ->get()
            ->groupBy('nik');

        // C. Ambil KPI Atasan (Hanya yang diapprove HR)
        $kpiAtasanDailies = KPIAtasanDaily::with('details')
            ->whereIn('nik', $nikList)
            ->whereBetween('tanggal', [$tglAwal, $tglAkhir])
            // ->where('status', 'approved_by_hr')
            ->orderBy('tanggal', 'desc') // Diurutkan dari yg terbaru
            ->get()
            ->groupBy('nik');

        // D. Ambil Semua Template Master KPI yang aktif
        $allMasterKPIs = KPIMaster::with('kpiMasterAtasan')
            ->where('is_active', 1)
            ->get();

        // =========================================================================
        // MULAI PROSES KALKULASI PER KARYAWAN
        // =========================================================================
        $rekap = [];
        DB::beginTransaction();
        try {
            foreach ($karyawans as $karyawan) {
                // Cari Template Master KPI yang sesuai (Berdasarkan Jabatan & Dept)
                $kpiMaster = $allMasterKPIs
                    ->where('jabatan_id', $karyawan->jabatan_id)
                    ->where('kode_dept', $karyawan->kode_dept)
                    ->first();

                if (! $kpiMaster) {
                    continue;
                } // Skip jika Karyawan tidak punya format KPI

                $items = [];
                $overallScore = 0;
                $weightTotal = 0;
                $atasanScoreSum = 0;

                // --------------------------------------------------
                // 1. HITUNG ABSENSI
                // --------------------------------------------------
                $bobotAtasanTotal = $kpiMaster->kpiMasterAtasan->where('is_active', 1)->sum('bobot_atasan');
                $bobotAbsensi = 100 - ($kpiMaster->bobot_kpi + $bobotAtasanTotal);

                $pStat = $presensiStats->get($karyawan->nik);
                $achieveAbsensi = ($pStat && $pStat->total_hari > 0)
                    ? round(($pStat->tepat_waktu / $pStat->total_hari) * 100)
                    : 0;

                $scoreAbsensi = min(100, $achieveAbsensi); // Maksimal 100%
                $totalScoreAbsensi = round(($bobotAbsensi * $scoreAbsensi) / 100);

                $items[] = (object) [
                    'objective' => 'Absensi',
                    'deliverable' => '% Datang Tepat Waktu',
                    'weight' => $bobotAbsensi,
                    'target' => '90%',
                    'achievement' => $achieveAbsensi,
                    'score' => $scoreAbsensi,
                    'total_score' => $totalScoreAbsensi,
                ];

                $overallScore += $totalScoreAbsensi;
                $weightTotal += $bobotAbsensi;

                // --------------------------------------------------
                // 2. HITUNG KPI KARYAWAN (WORKBOOK + EXTRA)
                // --------------------------------------------------
                $karyawanDailies = $kpiDailies->get($karyawan->nik, collect());
                $achieveKaryawan = 0;

                if ($karyawanDailies->isNotEmpty()) {
                    $totalScoreHarian = 0;
                    foreach ($karyawanDailies as $daily) {
                        $scoreUtama = $daily->kpiDailyDetail->sum('score');
                        $scoreExtra = $daily->kpiDailyExtra->sum('score'); // Extra masuk ke harian

                        $persenHariIni = (($scoreUtama + $scoreExtra) / $kpiMaster->bobot_kpi) * 100; // Menggunakan bobot dari Master KPI
                        if ($persenHariIni > 100) {
                            $persenHariIni = 100;
                        }

                        $totalScoreHarian += $persenHariIni;
                    }
                    $achieveKaryawan = round($totalScoreHarian / $karyawanDailies->count());
                }

                $scoreKaryawan = min(100, $achieveKaryawan);
                $totalScoreKaryawan = round(($kpiMaster->bobot_kpi * $scoreKaryawan) / 100);

                $items[] = (object) [
                    'objective' => 'KPI',
                    'deliverable' => '% Kecepatan dan ketepatan', // Sesuai Excel
                    'weight' => floatval($kpiMaster->bobot_kpi),
                    'target' => floatval($kpiMaster->target_kpi).'%',
                    'achievement' => $achieveKaryawan,
                    'score' => $scoreKaryawan,
                    'total_score' => $totalScoreKaryawan,
                ];

                $overallScore += $totalScoreKaryawan;
                $weightTotal += $kpiMaster->bobot_kpi;

                // --------------------------------------------------
                // 3. HITUNG PENILAIAN ATASAN
                // --------------------------------------------------
                $karyawanAtasanDailies = $kpiAtasanDailies->get($karyawan->nik, collect());
                $latestAtasan = $karyawanAtasanDailies->first(); // Ambil laporan Atasan terbaru

                foreach ($kpiMaster->kpiMasterAtasan->where('is_active', 1) as $atasanMaster) {
                    $achieveAtasan = 0;
                    if ($latestAtasan && $latestAtasan->details) {
                        $det = $latestAtasan->details->where('kpi_master_atasan_id', $atasanMaster->id)->first();
                        if ($det) {
                            $achieveAtasan = $det->score;
                        }
                    }

                    $scoreAtasan = min(100, $achieveAtasan);
                    $totScoreA = round(($atasanMaster->bobot_atasan * $scoreAtasan) / 100);

                    // Ambil kata pertama sebelum % untuk Objective (Misal "% inovasi" -> "Inovasi")
                    $cleanObjective = trim(str_replace('%', '', explode(' ', $atasanMaster->indikator)[0]));

                    $items[] = (object) [
                        'objective' => $cleanObjective ?: 'Lainnya',
                        'deliverable' => $atasanMaster->indikator,
                        'weight' => floatval($atasanMaster->bobot_atasan),
                        'target' => floatval($atasanMaster->target_atasan).'%',
                        'achievement' => $achieveAtasan,
                        'score' => $scoreAtasan,
                        'total_score' => $totScoreA,
                    ];

                    $overallScore += $totScoreA;
                    $weightTotal += $atasanMaster->bobot_atasan;
                    $atasanScoreSum += $totScoreA;
                }

                // --------------------------------------------------
                // 4. SIMPAN HASIL FINAL KE TABEL kpi_report
                // --------------------------------------------------
                KPIReport::updateOrInsert(
                    [
                        'nik' => $karyawan->nik,
                        'periode_bulan' => str_pad($bulan, 2, '0', STR_PAD_LEFT),
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

                // Kemas untuk dikirim ke View
                $rekap[] = (object) [
                    'nik' => $karyawan->nik,
                    'nama_lengkap' => $karyawan->nama_lengkap,
                    'jabatan' => $karyawan->jabatanRel->nama_jabatan ?? '-',
                    'departemen' => $karyawan->departemen->nama_dept ?? '-',
                    'cabang' => $karyawan->cabang->nama_cabang ?? '-',
                    'periode' => $periodeString,
                    'items' => $items,
                    'summary' => (object) [
                        'weight_total' => $weightTotal,
                        'overall_score' => $overallScore,
                    ],
                ];
            }
            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', $this->failMessage('Gagal membuat laporan.', $e));
        }

        $data = compact('bulan', 'tahun', 'namabulan', 'rekap', 'tglAwal', 'tglAkhir');

        if ($request->exportexcel == '1') {
            return response()
                ->view('admin.kpi.cetakreportkpiexcel', $data)
                ->header('Content-Type', 'application/vnd.ms-excel')
                ->header('Content-Disposition', "attachment; filename=Performance_Appraisal_{$periodeString}.xls");
        }

        return view('admin.kpi.cetakreportkpi', $data);
    }

    private function getHariKerjaEfektif($tglAwal, $tglAkhir, $nik)
    {
        $startDate = Carbon::parse($tglAwal);
        $endDate = Carbon::parse($tglAkhir);
        $totalDays = 0;

        $hariLiburDb = HariLibur::whereBetween('tanggal_libur', [$tglAwal, $tglAkhir])
            ->pluck('tanggal_libur')
            ->toArray();

        $tglIzinSakit = [];
        $izinRecords = Izin::where('nik', $nik)
            ->where('status_approved', '1')
            ->whereIn('status', ['i', 's', 'c', 'r'])
            ->where(function ($q) use ($tglAwal, $tglAkhir) {
                $q->whereBetween('tgl_izin_dari', [$tglAwal, $tglAkhir])
                    ->orWhereBetween('tgl_izin_sampai', [$tglAwal, $tglAkhir]);
            })->get();

        foreach ($izinRecords as $iz) {
            $startIzin = Carbon::parse($iz->tgl_izin_dari)->max(Carbon::parse($tglAwal));
            $endIzin = Carbon::parse($iz->tgl_izin_sampai)->min(Carbon::parse($tglAkhir));

            while ($startIzin <= $endIzin) {
                $tglIzinSakit[] = $startIzin->format('Y-m-d');
                $startIzin->addDay();
            }
        }

        while ($startDate <= $endDate) {
            $dateString = $startDate->format('Y-m-d');

            $isMinggu = $startDate->dayOfWeek === Carbon::SUNDAY;
            $isLiburNasional = in_array($dateString, $hariLiburDb);
            $isIzinSakit = in_array($dateString, $tglIzinSakit);

            if (! $isMinggu && ! $isLiburNasional && ! $isIzinSakit) {
                $totalDays++;
            }

            $startDate->addDay();
        }

        return $totalDays;
    }
}
