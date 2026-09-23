<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\KpiLeaderboardSnapshot;
use App\Models\KPIMaster;
use App\Models\RekapBulanan;
use App\Models\SalaryIncrease;
use App\Models\SuratPeringatan;
use App\Support\PeriodeKerja;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class KenaikanGajiController extends Controller
{
    protected $limitBulan = 6;

    public function index()
    {
        $karyawan = Auth::guard('karyawan')->user();
        $nik = $karyawan->nik;

        $siteBranches = array_map('trim', explode(',', get_setting('cabang_tambang', 'CBNG0003,CBNG0011,RBJ,TBKR,CBNG0002')));
        $isTambang = in_array($karyawan->kode_cabang, $siteBranches);
        $targetPoin = (int) ($isTambang ? get_setting('point_gaji_tambang', 750) : get_setting('point_gaji_kantor', 500));

        // Ambil data rekap terakhir berdasarkan batas bulan
        $rekapTerakhir = RekapBulanan::where('nik', $nik)
            ->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->limit($this->limitBulan)
            ->get();

        // Syarat 1: Rata-rata poin >= 500 selama periode bulan terakhir
        $totalPoin = $rekapTerakhir->sum('total_poin');
        $rataPoin = $rekapTerakhir->count() > 0 ? $totalPoin / $rekapTerakhir->count() : 0;
        $syaratPoin = $rataPoin >= $targetPoin && $rekapTerakhir->count() == $this->limitBulan;

        // Syarat 2: Izin/Sakit <= 6 hari/bulan
        $syaratIzinSakit = true;
        foreach ($rekapTerakhir as $rekap) {
            if ($rekap->total_izin_sakit > 6) {
                $syaratIzinSakit = false;
                break;
            }
        }
        if ($rekapTerakhir->count() < $this->limitBulan) {
            $syaratIzinSakit = false; // Belum cukup bulan bekerja
        }

        // Syarat 3: Tidak ada SP aktif
        $spAktif = SuratPeringatan::where('nik', $nik)
            ->whereDate('expires_at', '>=', Carbon::today())
            ->exists();
        $syaratSp = ! $spAktif;

        // Syarat 4: Jeda waktu minimal bulan dari pengajuan sebelumnya
        $pengajuanTerakhir = SalaryIncrease::where('nik', $nik)->orderBy('tanggal_pengajuan', 'desc')->first();
        $syaratJeda = true;
        if ($pengajuanTerakhir) {
            $jedaBulan = Carbon::parse($pengajuanTerakhir->tanggal_pengajuan)->diffInMonths(Carbon::now());
            if ($jedaBulan < $this->limitBulan) {
                $syaratJeda = false;
            }
        }

        // Syarat 5: KPI Minimal 80% (Dihitung dari KpiLeaderboardSnapshot)
        // Catatan: Sesuai permintaan user, kriteria ini dihitung & ditampilkan tetapi belum di-enforce (belum memblokir pendaftaran)
        $kpiResult = $this->calculateKpiAverage($nik, $rekapTerakhir);
        $rataKpi = $kpiResult['rataKpi'];
        $syaratKpi = $kpiResult['syaratKpi'];

        // Mengecek apakah sudah ada pengajuan pending
        $pengajuanPending = SalaryIncrease::where('nik', $nik)->where('status', 'pending')->exists();

        // Kriteria kelayakan: Syarat KPI belum dimasukkan ke sini demi keamanan (belum di-enforce)
        $isEligible = $syaratPoin && $syaratIzinSakit && $syaratSp && $syaratJeda && ! $pengajuanPending;

        // Hitung persentase: 15% untuk yang pertama, 10% kedua, 5% dst
        $jumlahPengajuanApproved = SalaryIncrease::where('nik', $nik)->where('status', 'approved')->count();
        $persentase = 15;
        if ($jumlahPengajuanApproved == 1) {
            $persentase = 10;
        } elseif ($jumlahPengajuanApproved >= 2) {
            $persentase = 5;
        }

        $historiPengajuan = SalaryIncrease::where('nik', $nik)->orderBy('tanggal_pengajuan', 'desc')->get();

        $limitBulan = $this->limitBulan;

        return view('karyawan.kenaikan_gaji.index', compact(
            'rataPoin', 'syaratPoin', 'targetPoin',
            'syaratIzinSakit', 'rekapTerakhir',
            'syaratSp',
            'syaratJeda', 'pengajuanTerakhir',
            'rataKpi', 'syaratKpi',
            'isEligible', 'persentase', 'pengajuanPending',
            'limitBulan', 'historiPengajuan'
        ));
    }

    public function store(Request $request)
    {
        $karyawan = Auth::guard('karyawan')->user();
        $nik = $karyawan->nik;

        $siteBranches = array_map('trim', explode(',', get_setting('cabang_tambang', 'CBNG0003,CBNG0011,RBJ,TBKR,CBNG0002')));
        $isTambang = in_array($karyawan->kode_cabang, $siteBranches);
        $targetPoin = (int) ($isTambang ? get_setting('point_gaji_tambang', 750) : get_setting('point_gaji_kantor', 500));

        // Pengecekan eligibility lagi di backend
        $rekapTerakhir = RekapBulanan::where('nik', $nik)
            ->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->limit($this->limitBulan)
            ->get();

        $totalPoin = $rekapTerakhir->sum('total_poin');
        $rataPoin = $rekapTerakhir->count() > 0 ? $totalPoin / $rekapTerakhir->count() : 0;
        $syaratPoin = $rataPoin >= $targetPoin && $rekapTerakhir->count() == $this->limitBulan;

        $syaratIzinSakit = true;
        foreach ($rekapTerakhir as $rekap) {
            if ($rekap->total_izin_sakit > 6) {
                $syaratIzinSakit = false;
                break;
            }
        }
        if ($rekapTerakhir->count() < $this->limitBulan) {
            $syaratIzinSakit = false;
        }

        $spAktif = SuratPeringatan::where('nik', $nik)
            ->whereDate('expires_at', '>=', Carbon::today())
            ->exists();
        $syaratSp = ! $spAktif;

        $pengajuanTerakhir = SalaryIncrease::where('nik', $nik)->orderBy('tanggal_pengajuan', 'desc')->first();
        $syaratJeda = true;
        if ($pengajuanTerakhir) {
            $jedaBulan = Carbon::parse($pengajuanTerakhir->tanggal_pengajuan)->diffInMonths(Carbon::now());
            if ($jedaBulan < $this->limitBulan) {
                $syaratJeda = false;
            }
        }

        // Syarat 5: KPI Minimal 80% (Dihitung dari KpiLeaderboardSnapshot namun belum dimasukkan ke penentu kelayakan $isEligible)
        $kpiResult = $this->calculateKpiAverage($nik, $rekapTerakhir);
        $rataKpi = $kpiResult['rataKpi'];
        $syaratKpi = $kpiResult['syaratKpi'];

        $pengajuanPending = SalaryIncrease::where('nik', $nik)->where('status', 'pending')->exists();

        // Kriteria kelayakan: Syarat KPI belum dimasukkan ke sini demi keamanan (belum di-enforce)
        $isEligible = $syaratPoin && $syaratIzinSakit && $syaratSp && $syaratJeda && ! $pengajuanPending;

        if (! $isEligible) {
            return redirect()->back()->with(['error' => 'Anda belum memenuhi kriteria untuk pengajuan kenaikan gaji.']);
        }

        $jumlahPengajuanApproved = SalaryIncrease::where('nik', $nik)->where('status', 'approved')->count();
        $persentase = 15;
        if ($jumlahPengajuanApproved == 1) {
            $persentase = 10;
        } elseif ($jumlahPengajuanApproved >= 2) {
            $persentase = 5;
        }

        SalaryIncrease::create([
            'nik' => $nik,
            'persentase' => $persentase,
            'tanggal_pengajuan' => Carbon::now()->format('Y-m-d'),
            'status' => 'pending',
            'catatan' => $request->catatan ?? null,
        ]);

        return redirect()->back()->with(['success' => 'Pengajuan kenaikan gaji berhasil dikirim dan sedang menunggu persetujuan HRD.']);
    }

    private function calculateKpiAverage($nik, $rekapTerakhir)
    {
        $karyawan = Auth::guard('karyawan')->user();

        // Cari Template Master KPI yang sesuai (Berdasarkan Jabatan & Dept & Cabang dengan fallback)
        $kpiMaster = KPIMaster::where('jabatan_id', $karyawan->jabatan_id)
            ->where('kode_dept', $karyawan->kode_dept)
            ->where('kode_cabang', $karyawan->kode_cabang)
            ->where('is_active', true)->first();

        if (! $kpiMaster) {
            $kpiMaster = KPIMaster::where('jabatan_id', $karyawan->jabatan_id)
                ->where('kode_dept', $karyawan->kode_dept)
                ->whereNull('kode_cabang')
                ->where('is_active', true)->first();
        }

        if (! $kpiMaster) {
            $kpiMaster = KPIMaster::where('jabatan_id', $karyawan->jabatan_id)
                ->whereNull('kode_dept')
                ->whereNull('kode_cabang')
                ->where('is_active', true)->first();
        }

        $maxPoinDaily = 40; // Default fallback
        if ($kpiMaster) {
            $sumIndikator = DB::table('kpi_master_detail')
                ->where('kode_master', $kpiMaster->kode_master)
                ->sum('score_indikator');
            if ($sumIndikator > 0) {
                $maxPoinDaily = $sumIndikator;
            }
        }

        $rataKpi = 0;
        $totalPoinKpi = 0;
        $totalHariKpi = 0;
        $monthsCount = 0;

        if ($rekapTerakhir->isNotEmpty()) {
            $monthsCount = $rekapTerakhir->count();

            // Urutkan untuk mendapatkan bulan terlama dan terbaru
            $sortedRekap = $rekapTerakhir->sortBy([
                ['tahun', 'asc'],
                ['bulan', 'asc'],
            ]);

            $firstRekap = $sortedRekap->first();
            $lastRekap = $sortedRekap->last();

            // Dari awal periode rekap terlama s/d akhir periode rekap terbaru.
            $tglAwal = PeriodeKerja::bulan($firstRekap->bulan, $firstRekap->tahun)->mulai;
            $tglAkhir = PeriodeKerja::bulan($lastRekap->bulan, $lastRekap->tahun)->selesai->endOfDay();

            // Query daily points dari KpiLeaderboardSnapshot
            $kpiSnapshots = KpiLeaderboardSnapshot::where('nik', $nik)
                ->whereBetween('date', [$tglAwal, $tglAkhir])
                ->get();

            $totalPoinKpi = $kpiSnapshots->sum('points');
            $totalHariKpi = $kpiSnapshots->count();

            if ($totalHariKpi > 0) {
                $rataKpi = ($totalPoinKpi / ($totalHariKpi * $maxPoinDaily)) * 100;
            }
        }

        $rataKpi = min(100, $rataKpi);
        $syaratKpi = $rataKpi >= 80 && $monthsCount == $this->limitBulan;

        return [
            'rataKpi' => $rataKpi,
            'syaratKpi' => $syaratKpi,
            'monthsCount' => $monthsCount,
        ];
    }
}
