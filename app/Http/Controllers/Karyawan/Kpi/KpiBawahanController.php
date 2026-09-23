<?php

namespace App\Http\Controllers\Karyawan\Kpi;

use App\Http\Controllers\Controller;
use App\Models\KPIAtasanDaily;
use App\Models\KPIAtasanDailyDetail;
use App\Models\KPIDaily;
use App\Models\KPIMaster;
use App\Services\KpiKaryawanService;
use App\Support\PeriodeKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Atasan meninjau, menilai & menyetujui, atau mengembalikan KPI harian bawahan langsungnya.
 */
class KpiBawahanController extends Controller
{
    public function __construct(private KpiKaryawanService $kpi) {}

    public function atasanIndex(Request $request)
    {
        $user = Auth::guard('karyawan')->user();
        $bawahan = $this->kpi->bawahan($user);
        $periode = $this->kpi->periode($request->bulan, $request->tahun);

        $reqNik = $request->nik;
        $riwayatKPI = collect();
        $penilaianAtasan = null;

        if (! empty($reqNik) && $bawahan->contains('nik', $reqNik)) {
            $riwayatKPI = KPIDaily::with(['karyawan', 'karyawan.jabatanRel'])
                ->where('kpi_daily.nik', $reqNik)
                ->whereBetween('kpi_daily.tanggal', [$periode['tglAwal'], $periode['tglAkhir']])
                ->leftJoin('kpi_leaderboard_snapshots', function ($join) use ($reqNik) {
                    $join->on('kpi_daily.tanggal', '=', 'kpi_leaderboard_snapshots.date')
                        ->where('kpi_leaderboard_snapshots.nik', '=', $reqNik);
                })
                ->select('kpi_daily.*', 'kpi_leaderboard_snapshots.points as daily_points')
                ->orderBy('kpi_daily.tanggal', 'asc')
                ->get()
                ->map(fn ($item) => $this->kpi->tandaiNamaApprover($item));

            $penilaianAtasan = KPIAtasanDaily::where('nik', $reqNik)
                ->whereBetween('tanggal', [$periode['tglAwal'], $periode['tglAkhir']])
                ->first();
        }

        return view('karyawan.kpi.atasankpi', [
            'riwayatKPI' => $riwayatKPI,
            'bawahan' => $bawahan,
            'periodeList' => $periode['periodeList'],
            'tahunSekarang' => date('Y'),
            'tahunMulai' => date('Y') - 5,
            'reqBulan' => $periode['bulan'],
            'reqTahun' => $periode['tahun'],
            'reqNik' => $reqNik,
            'penilaianAtasan' => $penilaianAtasan,
            'tglAwal' => $periode['tglAwal'],
            'tglAkhir' => $periode['tglAkhir'],
        ]);
    }

    public function atasanDetailKPI($kpi_daily_id)
    {
        $kpiDaily = KPIDaily::with(['karyawan.jabatanRel', 'kpiDailyDetail.kpiMasterDetail', 'kpiDailyExtra'])->findOrFail($kpi_daily_id);

        if (! $this->atasanDari($kpiDaily)) {
            return redirect()->route('kpi.atasan.index')->with('error', 'Anda tidak memiliki akses ke data ini.');
        }

        $kpiDaily = $this->kpi->tandaiNamaApprover($kpiDaily);
        $kpiAtasanDaily = KPIAtasanDaily::with('details')
            ->where('nik', $kpiDaily->nik)
            ->whereDate('tanggal', $kpiDaily->tanggal)
            ->first();
        $periodeKpi = PeriodeKerja::dari($kpiDaily->tanggal);

        return view('karyawan.kpi.detailatasankpi', [
            'kpiDaily' => $kpiDaily,
            'namaAtasan' => $kpiDaily->nama_atasan_display,
            'namaHR' => $kpiDaily->nama_hr_display,
            'nikBack' => $kpiDaily->nik,
            'bulanBack' => $periodeKpi->selesai->format('m'),
            'tahunBack' => $periodeKpi->selesai->format('Y'),
            'indikators' => $this->kpi->indikator(KPIMaster::untukKaryawan($kpiDaily->karyawan), 'atasan'),
            'details' => $kpiAtasanDaily ? $kpiAtasanDaily->details->keyBy('kpi_master_atasan_id') : collect(),
            'isApproved' => in_array($kpiDaily->status, ['approved_by_atasan', 'approved_by_hr']),
        ]);
    }

    public function approveKPI(Request $request, $kpi_daily_id)
    {
        $user = Auth::guard('karyawan')->user();
        $kpiDaily = KPIDaily::findOrFail($kpi_daily_id);

        if (! $this->atasanDari($kpiDaily)) {
            return back()->with('error', 'Akses ditolak.');
        }

        if ($kpiDaily->status != 'submitted') {
            return back()->with('error', 'Status KPI sudah berubah, tidak dapat disetujui.');
        }

        $request->validate([
            'kpi' => 'required|array',
            'kpi.*.foto' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        // Hanya indikator penilaian atasan dari master KPI bawahan ini (bobot diambil dari master).
        $indikator = $this->kpi->indikator(KPIMaster::untukKaryawan($kpiDaily->karyawan), 'atasan')->keyBy('id');
        if (array_diff(array_keys($request->kpi), $indikator->keys()->all())) {
            return back()->with('error', 'Indikator penilaian tidak sesuai dengan master KPI karyawan.')->withInput();
        }

        try {
            DB::transaction(function () use ($request, $kpiDaily, $user, $indikator) {
                $kpiDaily->update([
                    'status' => 'approved_by_atasan',
                    'approve_atasan' => $user->nik,
                    'approve_atasan_at' => now(),
                    'alasan_reject' => null,
                ]);

                $penilaian = KPIAtasanDaily::updateOrCreate(
                    ['nik' => $kpiDaily->nik, 'tanggal' => $kpiDaily->tanggal],
                    ['input_atasan' => $user->nik, 'status' => 'submitted']
                );

                foreach ($request->kpi as $masterId => $data) {
                    $existing = KPIAtasanDailyDetail::where('kpi_atasan_daily_id', $penilaian->id)->where('kpi_master_atasan_id', $masterId)->first();

                    $pathFoto = $existing->bukti_foto ?? null;
                    if ($request->hasFile("kpi.$masterId.foto")) {
                        if ($existing?->bukti_foto) {
                            Storage::disk('public')->delete($existing->bukti_foto);
                        }
                        $pathFoto = $request->file("kpi.$masterId.foto")->store('uploads/kpi_atasan', 'public');
                    }

                    $isChecked = (isset($data['is_checked']) && $data['is_checked'] == 1) ? 1 : 0;

                    KPIAtasanDailyDetail::updateOrCreate(
                        ['kpi_atasan_daily_id' => $penilaian->id, 'kpi_master_atasan_id' => $masterId],
                        [
                            'is_checked' => $isChecked,
                            'score' => $isChecked ? ($indikator[$masterId]->bobot_atasan ?? 0) : 0,
                            'catatan' => $data['catatan'] ?? null,
                            'bukti_foto' => $pathFoto,
                        ]
                    );
                }
            });

            return $this->keRiwayatBawahan($kpiDaily)->with('success', 'KPI Karyawan beserta Penilaian Atasan berhasil disetujui & disimpan.');
        } catch (Throwable $e) {
            return back()->with('error', $this->failMessage('Terjadi kesalahan sistem saat memproses persetujuan.', $e))->withInput();
        }
    }

    public function rejectKPI(Request $request, $kpi_daily_id)
    {
        $request->validate(['alasan_reject' => 'required|string']);

        $kpiDaily = KPIDaily::findOrFail($kpi_daily_id);

        if (! $this->atasanDari($kpiDaily)) {
            return back()->with('error', 'Akses ditolak.');
        }

        if ($kpiDaily->status != 'submitted') {
            return back()->with('error', 'Status KPI sudah berubah, tidak dapat dikembalikan.');
        }

        $kpiDaily->update(['status' => 'rejected', 'alasan_reject' => $request->alasan_reject]);

        return $this->keRiwayatBawahan($kpiDaily)->with('warning', 'KPI telah dikembalikan ke karyawan untuk direvisi.');
    }

    /**
     * Hanya atasan langsung (bukan diri sendiri / rekan setingkat) yang boleh meninjau & memutuskan.
     */
    private function atasanDari(KPIDaily $kpiDaily): bool
    {
        return $kpiDaily->karyawan
            && $this->kpi->peran(Auth::guard('karyawan')->user(), $kpiDaily->karyawan) === 'atasan';
    }

    private function keRiwayatBawahan(KPIDaily $kpiDaily)
    {
        $periode = PeriodeKerja::dari($kpiDaily->tanggal);

        return redirect()->route('kpi.atasan.index', [
            'nik' => $kpiDaily->nik,
            'bulan' => $periode->selesai->format('m'),
            'tahun' => $periode->selesai->format('Y'),
        ]);
    }
}
