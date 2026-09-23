<?php

namespace App\Http\Controllers\Admin\Kpi;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\Karyawan;
use App\Models\KPIAtasanDaily;
use App\Models\KPIDaily;
use App\Models\KPIDailyDetail;
use App\Models\KPIDailyExtra;
use App\Models\KPIMaster;
use App\Models\KPIMasterAtasan;
use App\Models\KPIMasterDetail;
use App\Support\PeriodeKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Verifikasi HR atas KPI harian karyawan: detail, koreksi, approve/reject, dan persetujuan massal.
 */
class VerifikasiKpiController extends Controller
{
    public function indikatorKPI(Request $request)
    {
        $reqBulan = $request->input('bulan', date('m'));
        $reqTahun = $request->input('tahun', date('Y'));
        $nik = $request->input('nik');

        if (! $nik) {
            return redirect()->route('kpi.rekap.karyawan')->with('error', 'Silakan pilih karyawan terlebih dahulu.');
        }

        $karyawan = Karyawan::with(['jabatanRel', 'departemen', 'cabang'])->where('nik', $nik)->firstOrFail();

        [$tglAwal, $tglAkhir] = PeriodeKerja::bulan($reqBulan, $reqTahun)->range();

        $riwayatKPI = KPIDaily::with(['kpiDailyDetail', 'kpiDailyExtra'])
            ->where('nik', $nik)
            ->whereBetween('tanggal', [$tglAwal, $tglAkhir])
            ->orderBy('tanggal', 'asc')
            ->get();

        $user = Auth::guard('user')->user();
        $canEdit = false;
        if ($user) {
            $canEdit = $user->hasRole(['administrator', 'hrd', 'admin cabang']);
        }

        return view('admin.kpi.indikatorkpi', compact(
            'karyawan', 'riwayatKPI', 'reqBulan', 'reqTahun', 'tglAwal', 'tglAkhir', 'canEdit'
        ));
    }

    public function detailIndikatorKPI($kpi_daily_id)
    {
        $kpiDaily = KPIDaily::with([
            'karyawan',
            'kpiDailyDetail' => function ($query) {
                $query->orderBy('kpi_master_detail_id', 'asc');
            },
            'kpiDailyExtra',
        ])->findOrFail($kpi_daily_id);

        $jabatanId = $kpiDaily->karyawan->jabatan_id;
        $kpiMaster = KPIMaster::with(['kpiMasterDetail' => function ($query) {
            $query->orderBy('id', 'asc');
        }])
            ->where('jabatan_id', $jabatanId)
            ->where('kode_dept', $kpiDaily->karyawan->kode_dept)
            ->where('kode_cabang', $kpiDaily->karyawan->kode_cabang)
            ->first();

        if (! $kpiMaster) {
            $kpiMaster = KPIMaster::with(['kpiMasterDetail' => function ($query) {
                $query->orderBy('id', 'asc');
            }])
                ->whereHas('jabatan', function ($query) {
                    $query->where('nama_jabatan', 'ilike', '%staff%');
                })
                ->where('kode_dept', $kpiDaily->karyawan->kode_dept)
                ->first();
        }

        $indikatorsAtasan = $kpiMaster ? KPIMasterAtasan::where('kode_master', $kpiMaster->kode_master)->get() : collect();
        $penilaianAtasan = KPIAtasanDaily::with(['details'])
            ->where('nik', $kpiDaily->nik)
            ->whereDate('tanggal', $kpiDaily->tanggal)
            ->first();

        Gate::authorize('verifikasi', $kpiDaily);
        $isHR = true;
        $canEdit = Gate::allows('ubah', $kpiDaily);

        $periodeKpi = PeriodeKerja::dari($kpiDaily->tanggal);
        $bulanBack = $periodeKpi->selesai->format('m');
        $tahunBack = $periodeKpi->selesai->format('Y');

        return view('admin.kpi.detailindikatorkpi', compact(
            'kpiDaily', 'kpiMaster', 'isHR', 'canEdit', 'bulanBack', 'tahunBack',
            'indikatorsAtasan', 'penilaianAtasan'
        ));
    }

    public function updateDetailIndikatorKPI(Request $request, $kpi_daily_id)
    {
        $request->validate([
            'details' => 'required|array',
            'details.*.catatan' => 'nullable|string',
        ]);

        $kpiDaily = KPIDaily::with([
            'karyawan.jabatanRel',
            'kpiDailyDetail.kpiMasterDetail',
            'kpiDailyExtra',
        ])->findOrFail($kpi_daily_id);

        if ($kpiDaily->status === 'approved_by_hr') {
            return back()->with('error', 'Data sudah disetujui HR dan tidak dapat diubah.');
        }

        Gate::authorize('ubah', $kpiDaily);

        DB::beginTransaction();
        try {
            foreach ($request->details as $detailId => $data) {

                $isChecked = isset($data['is_checked']) ? 1 : 0;
                $masterDetail = KPIMasterDetail::find($detailId);
                $score = $isChecked ? ($masterDetail->score_indikator ?? 0) : 0;

                KPIDailyDetail::updateOrCreate(
                    [
                        'kpi_daily_id' => $kpi_daily_id,
                        'kpi_master_detail_id' => $detailId,
                    ],
                    [
                        'score' => $score,
                        'is_checked' => $isChecked,
                        'catatan' => $data['catatan'] ?? null,
                    ]
                );
            }

            DB::commit();

            return back()->with('success', 'Detail penilaian dan verifikasi berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', $this->failMessage('Gagal update.', $e));
        }
    }

    public function approveKPI(Request $request, $kpi_daily_id)
    {
        $kpiDaily = KPIDaily::with('karyawan')->findOrFail($kpi_daily_id);
        Gate::authorize('verifikasi', $kpiDaily);

        if ($request->input('action') !== 'approve_hr') {
            return back()->with('error', 'Status KPI tidak valid untuk diapprove saat ini.');
        }

        $approvedBy = (string) Auth::guard('user')->id();

        try {
            DB::transaction(function () use ($kpiDaily, $approvedBy) {
                $kpiDaily->update([
                    'status' => 'approved_by_hr',
                    'approve_hr' => $approvedBy,
                    'approve_hr_at' => now(),
                    'alasan_reject' => null,
                ]);

                KPIAtasanDaily::where('nik', $kpiDaily->nik)
                    ->where('tanggal', $kpiDaily->tanggal)
                    ->update([
                        'status' => 'approved_by_hr',
                        'approve_hr' => $approvedBy,
                        'approve_hr_at' => now(),
                    ]);
            });

            return back()->with('success', 'Workbook Karyawan dan Penilaian Atasan berhasil disetujui sepenuhnya oleh HR.');
        } catch (\Exception $e) {
            return back()->with('error', $this->failMessage('Gagal menyetujui KPI.', $e));
        }
    }

    public function rejectKPI(Request $request, $kpi_daily_id)
    {
        $request->validate([
            'alasan_reject' => 'required|string',
        ]);

        $kpiDaily = KPIDaily::with('karyawan')->findOrFail($kpi_daily_id);
        Gate::authorize('verifikasi', $kpiDaily);

        DB::beginTransaction();
        try {
            $kpiDaily->update([
                'status' => 'rejected',
                'alasan_reject' => $request->alasan_reject,
                'approve_atasan' => null,
                'approve_atasan_at' => null,
                'approve_hr' => null,
                'approve_hr_at' => null,
            ]);

            KPIAtasanDaily::where('nik', $kpiDaily->nik)
                ->where('tanggal', $kpiDaily->tanggal)
                ->update([
                    'status' => 'draft',
                    'approve_hr' => null,
                    'approve_hr_at' => null,
                ]);

            DB::commit();

            return back()->with('success', 'KPI Ditolak dan dikembalikan ke karyawan untuk direvisi.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', $this->failMessage('Gagal menolak KPI.', $e));
        }
    }

    public function updateExtra(Request $request, $id)
    {
        $extra = KPIDailyExtra::with('kpiDaily.karyawan')->findOrFail($id);
        Gate::authorize('ubah', $extra->kpiDaily);

        $request->validate([
            'indikator_tambahan' => 'required|string|max:255',
            'catatan' => 'nullable|string',
            'score' => 'required|numeric|min:0',
        ]);

        $extra->update([
            'indikator_tambahan' => $request->indikator_tambahan,
            'catatan' => $request->catatan,
            'score' => $request->score,
        ]);

        return back()->with('success', 'KPI Tambahan berhasil diupdate.');
    }

    public function destroyExtra($id)
    {
        $extra = KPIDailyExtra::with('kpiDaily.karyawan')->findOrFail($id);
        Gate::authorize('ubah', $extra->kpiDaily);
        $extra->delete();

        return back()->with('success', 'KPI Tambahan berhasil dihapus.');
    }

    public function bulkApproveHR(Request $request)
    {
        $request->validate([
            'niks' => 'required|array|min:1',
            'bulan' => 'required',
            'tahun' => 'required',
        ], [
            'niks.required' => 'Pilih minimal satu karyawan untuk disetujui.',
        ]);

        Gate::authorize('verifikasiMassal', KPIDaily::class);

        $user = Auth::guard('user')->user();
        $hrIdentifier = (string) $user->id;
        // Admin cabang hanya boleh menyetujui karyawan di cabangnya.
        $niks = Karyawan::visibleTo($user)->whereIn('nik', $request->niks)->pluck('nik');

        [$tglAwal, $tglAkhir] = PeriodeKerja::bulan($request->bulan, $request->tahun)->range();

        DB::beginTransaction();
        try {
            // 1. Bulk Approve KPI Karyawan (Workbook)
            $updatedDaily = KPIDaily::whereIn('nik', $niks)
                ->whereBetween('tanggal', [$tglAwal, $tglAkhir])
                ->whereNull('approve_hr')
                // ->where('status', 'approved_by_atasan')
                ->update([
                    'status' => 'approved_by_hr',
                    'approve_hr' => $hrIdentifier,
                    'approve_hr_at' => now(),
                ]);

            // 2. Bulk Approve KPI Atasan
            $updatedAtasanDaily = KPIAtasanDaily::whereIn('nik', $niks)
                ->whereBetween('tanggal', [$tglAwal, $tglAkhir])
                ->whereNull('approve_hr')
                // ->where('status', 'submitted')
                ->update([
                    'status' => 'approved_by_hr',
                    'approve_hr' => $hrIdentifier,
                    'approve_hr_at' => now(),
                ]);

            DB::commit();

            // if ($updatedDaily == 0 && $updatedAtasanDaily == 0) {
            //     return redirect()->back()->with('error', 'Tidak ada data KPI yang bisa disetujui. Pastikan Atasan telah menyetujui laporan tersebut terlebih dahulu.');
            // }

            return redirect()->back()->with('success', "Berhasil menyetujui $updatedDaily laporan KPI harian dan $updatedAtasanDaily penilaian atasan.");

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', $this->failMessage('Terjadi kesalahan saat Bulk Approve.', $e));
        }
    }
}
