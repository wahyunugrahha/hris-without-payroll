<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\Departemen;
use App\Models\HariLibur;
use App\Models\Izin;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\KPIAtasanDaily;
use App\Models\KPIDaily;
use App\Models\KPIDailyDetail;
use App\Models\KPIDailyExtra;
use App\Models\KPIMaster;
use App\Models\KPIMasterAtasan;
use App\Models\KPIMasterDetail;
use App\Models\KPIReport;
use App\Support\PeriodeKerja;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class KPIController extends Controller
{
    // ==========================================
    // Master KPI Management
    // ==========================================
    public function masterKPI(Request $request)
    {
        $user = Auth::guard('user')->user();
        $isAdminCabang = $user->isAdminCabang();
        $isHR = $user->hasRole(['hrd', 'administrator']);

        $kpiMaster = KPIMaster::with(['departemen', 'jabatan'])
            ->select(
                'kode_master',
                'nama_kpi',
                'jabatan_id',
                'kode_dept',
                'is_active',
            )
            ->selectRaw('MAX(id) as id')
            ->selectRaw('MAX(updated_at) as latest_update')
            ->when($isAdminCabang, function ($q) use ($user) {
                $q->where('kode_cabang', $user->kode_cabang);
            })
            ->when($request->filled('indikator'), function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('nama_kpi', 'ilike', "%{$request->indikator}%")
                        ->orWhere('kode_master', 'ilike', "%{$request->indikator}%");
                });
            })
            ->when($request->filled('kode_dept'), function ($q) use ($request) {
                $q->where('kode_dept', $request->kode_dept);
            })
            ->when($request->filled('jabatan_id'), function ($q) use ($request) {
                $q->where('jabatan_id', $request->jabatan_id);
            })
            ->when($isHR && $request->filled('kode_cabang'), function ($q) use ($request) {
                $q->where('kode_cabang', $request->kode_cabang);
            })
            ->groupBy('kode_master', 'nama_kpi', 'jabatan_id', 'kode_dept', 'is_active')
            ->orderBy('latest_update', 'desc')
            ->paginate(25)
            ->withQueryString();

        $jabatan = Jabatan::orderBy('nama_jabatan')->get();
        $departemen = Departemen::orderBy('nama_dept')->get();
        $cabang = $isAdminCabang
            ? Cabang::where('kode_cabang', $user->kode_cabang)->get()
            : Cabang::orderBy('nama_cabang')->get();

        return view('admin.kpi.masterkpi', compact(
            'departemen', 'cabang', 'jabatan', 'kpiMaster'
        ));
    }

    public function storeMasterKPI(Request $request)
    {
        $request->validate([
            'kode_master' => 'required|string',
            'nama_kpi' => 'required|string|max:255',
            'jabatan_id' => 'required|exists:jabatan,id',
            'kode_dept' => 'required|exists:departemen,kode_dept',
            'kode_cabang' => 'required|array|min:1',
            'kode_cabang.*' => 'required|string|exists:cabang,kode_cabang',
            'is_active' => 'required|in:0,1',
        ], [
            'kode_cabang.required' => 'Minimal harus memilih 1 cabang!',
            'kode_cabang.min' => 'Minimal harus memilih 1 cabang!',
        ]);

        DB::beginTransaction();
        try {
            $existingKpi = KPIMaster::where('kode_master', $request->kode_master)->first();

            $bobotKpi = $existingKpi ? $existingKpi->bobot_kpi : 100;
            $targetKpi = $existingKpi ? $existingKpi->target_kpi : 100;

            $createdCount = 0;
            foreach ($request->kode_cabang as $kodeCabang) {
                KPIMaster::create([
                    'kode_master' => $request->kode_master,
                    'nama_kpi' => $request->nama_kpi,
                    'jabatan_id' => $request->jabatan_id,
                    'kode_dept' => $request->kode_dept,
                    'kode_cabang' => $kodeCabang,
                    'is_active' => (bool) $request->is_active,
                    'bobot_kpi' => $bobotKpi,
                    'target_kpi' => $targetKpi,
                ]);
                $createdCount++;
            }

            DB::commit();

            return redirect()->route('kpi.master.index')
                ->with('success', 'Data KPI Master berhasil ditambahkan untuk '.$createdCount.' cabang');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', $this->failMessage('Gagal menambah data.', $e))->withInput();
        }
    }

    public function editMasterKPI($id)
    {
        try {
            $user = Auth::guard('user')->user();
            $isAdminCabang = $user->isAdminCabang();

            $kpi = KPIMaster::findOrFail($id);
            $kodeMaster = $kpi->kode_master;

            $jabatan = Jabatan::orderBy('nama_jabatan')->get();
            $departemen = Departemen::orderBy('nama_dept')->get();

            $cabang = $isAdminCabang
                ? Cabang::where('kode_cabang', $user->kode_cabang)->get()
                : Cabang::orderBy('nama_cabang')->get();

            $selectedCabangs = KPIMaster::where('kode_master', $kodeMaster)
                ->pluck('kode_cabang')
                ->toArray();

            return view('admin.kpi.editmasterkpi', compact(
                'kpi', 'departemen', 'cabang', 'jabatan', 'selectedCabangs'
            ));
        } catch (\Exception $e) {
            return back()->with('error', $this->failMessage('Gagal memuat data.', $e));
        }
    }

    public function updateMasterKPI(Request $request, $id)
    {
        $request->validate([
            'kode_master' => 'required|string',
            'nama_kpi' => 'required|string|max:255',
            'jabatan_id' => 'required|exists:jabatan,id',
            'kode_dept' => 'required|exists:departemen,kode_dept',
            'kode_cabang' => 'required|array|min:1',
            'kode_cabang.*' => 'required|string|exists:cabang,kode_cabang',
            'is_active' => 'required|in:0,1',
        ], [
            'kode_cabang.required' => 'Minimal harus memilih 1 cabang!',
            'kode_cabang.min' => 'Minimal harus memilih 1 cabang!',
        ]);

        DB::beginTransaction();
        try {
            $kpi = KPIMaster::findOrFail($id);
            $kodeMasterLama = $kpi->kode_master;
            $kodeMasterBaru = $request->kode_master;

            $bobotKpiSaatIni = $kpi->bobot_kpi;
            $targetKpiSaatIni = $kpi->target_kpi;

            KPIMaster::where('kode_master', $kodeMasterLama)->update([
                'kode_master' => $kodeMasterBaru,
                'nama_kpi' => $request->nama_kpi,
                'jabatan_id' => $request->jabatan_id,
                'kode_dept' => $request->kode_dept,
                'is_active' => (bool) $request->is_active,
            ]);

            if ($kodeMasterLama !== $kodeMasterBaru) {
                DB::table('kpi_master_detail')
                    ->where('kode_master', $kodeMasterLama)
                    ->update(['kode_master' => $kodeMasterBaru]);

                DB::table('kpi_master_atasan')
                    ->where('kode_master', $kodeMasterLama)
                    ->update(['kode_master' => $kodeMasterBaru]);
            }

            $existingCabangs = KPIMaster::where('kode_master', $kodeMasterBaru)->pluck('kode_cabang')->toArray();
            $newCabangs = $request->kode_cabang;

            $cabangToDelete = array_diff($existingCabangs, $newCabangs);
            if (! empty($cabangToDelete)) {
                KPIMaster::where('kode_master', $kodeMasterBaru)
                    ->whereIn('kode_cabang', $cabangToDelete)
                    ->delete();
            }

            $cabangToAdd = array_diff($newCabangs, $existingCabangs);
            foreach ($cabangToAdd as $kodeCabang) {
                KPIMaster::create([
                    'kode_master' => $kodeMasterBaru,
                    'nama_kpi' => $request->nama_kpi,
                    'jabatan_id' => $request->jabatan_id,
                    'kode_dept' => $request->kode_dept,
                    'kode_cabang' => $kodeCabang,
                    'is_active' => (bool) $request->is_active,
                    'bobot_kpi' => $bobotKpiSaatIni,
                    'target_kpi' => $targetKpiSaatIni,
                ]);
            }

            DB::commit();

            return redirect()->route('kpi.master.index')
                ->with('success', 'Data KPI Master berhasil diperbarui. Cabang aktif: '.count($newCabangs));
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', $this->failMessage('Gagal update data.', $e))->withInput();
        }
    }

    public function deleteMasterKPI($id)
    {
        try {
            $kpi = KPIMaster::findOrFail($id);
            $kodeMaster = $kpi->kode_master;

            KPIMasterDetail::where('kode_master', $kodeMaster)->delete();
            KPIMasterAtasan::where('kode_master', $kodeMaster)->delete();
            KPIMaster::where('kode_master', $kodeMaster)->delete();

            return redirect()->route('kpi.master.index')
                ->with('success', 'Data KPI Master beserta Indikator Detail dan Cabang terkait berhasil dihapus');
        } catch (\Exception $e) {
            return back()->with('error', $this->failMessage('Gagal menghapus data.', $e));
        }
    }

    // ==========================================
    // Detail Master KPI Management
    // ==========================================
    public function detailMasterKPI(Request $request, $id)
    {
        $kpiMaster = KPIMaster::with([
            'jabatan',
            'departemen',
            'kpiMasterDetail' => function ($q) {
                $q->orderBy('id', 'asc');
            },
            'kpiMasterAtasan' => function ($q) {
                $q->orderBy('id', 'asc');
            },
        ])->findOrFail($id);

        $karyawanDetails = $kpiMaster->kpiMasterDetail;
        $atasanDetails = $kpiMaster->kpiMasterAtasan;

        return view('admin.kpi.detailmasterkpi', compact('kpiMaster', 'karyawanDetails', 'atasanDetails'));
    }

    public function storeDetailMasterKPI(Request $request, $id)
    {
        $kpiMaster = KPIMaster::findOrFail($id);
        $request->merge([
            'bobot' => str_replace(',', '.', $request->bobot),
            'target' => $request->target ? str_replace(',', '.', $request->target) : null,
        ]);

        $request->validate([
            'indikator' => 'required|string',
            'bobot' => 'required|numeric|min:0',
            'target' => 'nullable|required_if:kategori,atasan|numeric|min:0',
            'kategori' => 'required|in:karyawan,atasan',
            'is_active' => 'required|boolean',
        ]);

        if ($request->kategori === 'karyawan') {
            $maxBobotKaryawan = $kpiMaster->bobot_kpi ?? 100;
            $currentTotal = KPIMasterDetail::where('kode_master', $kpiMaster->kode_master)
                ->where('is_active', true)
                ->sum('score_indikator');

            if ($request->is_active && ($currentTotal + $request->bobot) > $maxBobotKaryawan) {
                return back()->with('warning', 'Gagal! Total score untuk Karyawan akan menjadi '.($currentTotal + $request->bobot)."%. Maksimal {$maxBobotKaryawan}%.")->withInput();
            }

            KPIMasterDetail::create([
                'kode_master' => $kpiMaster->kode_master,
                'indikator' => $request->indikator,
                'score_indikator' => $request->bobot,
                'target' => 1,
                'is_active' => $request->is_active,
            ]);

        } else {
            KPIMasterAtasan::create([
                'kode_master' => $kpiMaster->kode_master,
                'indikator' => $request->indikator,
                'bobot_atasan' => $request->bobot,
                'target_atasan' => $request->target,
                'is_active' => $request->is_active,
            ]);
        }

        return back()->with('success', 'Indikator berhasil ditambahkan '.ucfirst($request->kategori));
    }

    public function updateDetailMasterKPI(Request $request)
    {
        $request->validate([
            'kode_master' => 'required|exists:kpi_master,kode_master',
            'bobot_kpi' => 'required|numeric|min:0',
            'target_kpi' => 'nullable|required|numeric|min:0',
            'details_karyawan' => 'nullable|array',
            'details_atasan' => 'nullable|array',
            'details_karyawan.*.score_indikator' => 'nullable|numeric',
            'details_atasan.*.bobot' => 'nullable|numeric',
            'details_atasan.*.target' => 'nullable|numeric',
        ]);

        DB::beginTransaction();
        try {
            KPIMaster::where('kode_master', $request->kode_master)->update([
                'bobot_kpi' => $request->bobot_kpi,
                'target_kpi' => $request->target_kpi,
            ]);

            $master = KPIMaster::where('kode_master', $request->kode_master)->first();

            if ($request->has('details_karyawan')) {
                $totalBobotKaryawan = 0;
                foreach ($request->details_karyawan as $detailId => $data) {
                    $detail = KPIMasterDetail::find($detailId);
                    if ($detail) {
                        $detail->update([
                            'indikator' => $data['indikator'],
                            'score_indikator' => $data['score_indikator'],
                            'is_active' => $data['is_active'],
                        ]);

                        if ($data['is_active'] == 1) {
                            $totalBobotKaryawan += $data['score_indikator'];
                        }
                    }
                }

                $maxBobot = $master->bobot_kpi ?? 100;
                if ($totalBobotKaryawan > $maxBobot) {
                    DB::rollBack();

                    return back()->with('warning', "Data gagal disimpan! Total bobot Karyawan melebihi batas (Aktif: {$totalBobotKaryawan}% / Maks: {$maxBobot}%). Harap perbaiki kembali.");
                }
            }

            // 3. UPDATE DETAILS ATASAN
            if ($request->has('details_atasan')) {
                foreach ($request->details_atasan as $detailId => $data) {
                    $detail = KPIMasterAtasan::find($detailId);
                    if ($detail) {
                        $detail->update([
                            'indikator' => $data['indikator'],
                            'bobot_atasan' => $data['bobot'],
                            'target_atasan' => $data['target'],
                            'is_active' => $data['is_active'],
                        ]);
                    }
                }
            }

            DB::commit();

            return back()->with('success', 'Data Pengaturan KPI dan Indikator berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', $this->failMessage('Gagal memproses data.', $e));
        }
    }

    public function deleteDetailMasterKPI($detail_id, $jenis)
    {
        if ($jenis == 'karyawan') {
            KPIMasterDetail::findOrFail($detail_id)->delete();
        } elseif ($jenis == 'atasan') {
            KPIMasterAtasan::findOrFail($detail_id)->delete();
        }

        return back()->with('success', 'Indikator dihapus.');
    }

    // ==========================================
    // Indikator Harian & Approval
    // ==========================================
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

        $user = Auth::guard('user')->user();
        $isHR = $user->hasRole(['administrator', 'hrd', 'admin cabang']);

        if (! $isHR) {
            abort(403, 'Akses Ditolak.');
        }

        $canEdit = $isHR && $kpiDaily->status != 'approved_by_hr';

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

        $user = Auth::guard('user')->user();
        $isHR = $user->hasRole(['administrator', 'hrd', 'admin cabang']);

        if ($kpiDaily->status === 'approved_by_hr') {
            return back()->with('error', 'Data sudah disetujui HR dan tidak dapat diubah.');
        }

        if ($kpiDaily->status === 'approved_by_atasan' && ! $isHR) {
            return back()->with('error', 'Data sudah disetujui Atasan.');
        }

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
        $kpiDaily = KPIDaily::with([
            'karyawan.jabatanRel',
            'kpiDailyDetail.kpiMasterDetail',
            'kpiDailyExtra',
        ])->findOrFail($kpi_daily_id);

        $action = $request->input('action');
        $user = Auth::guard('user')->user();
        $isHR = $user->hasRole(['administrator', 'hrd', 'admin cabang']);

        if ($action === 'approve_hr') {
            if ($isHR) {

                // if ($kpiDaily->status !== 'approved_by_atasan') {
                //     return back()->with('error', 'Gagal! Laporan ini harus disetujui dan diberi penilaian oleh Atasan terlebih dahulu.');
                // }

                DB::beginTransaction();
                try {
                    $kpiDaily->update([
                        'status' => 'approved_by_hr',
                        'approve_hr' => (string) $user->id,
                        'approve_hr_at' => now(),
                        'alasan_reject' => null,
                    ]);

                    KPIAtasanDaily::where('nik', $kpiDaily->nik)
                        ->where('tanggal', $kpiDaily->tanggal)
                        ->update([
                            'status' => 'approved_by_hr',
                            'approve_hr' => (string) $user->id,
                            'approve_hr_at' => now(),
                        ]);

                    DB::commit();

                    return back()->with('success', 'Workbook Karyawan dan Penilaian Atasan berhasil disetujui sepenuhnya oleh HR.');

                } catch (\Exception $e) {
                    DB::rollBack();

                    return back()->with('error', $this->failMessage('Gagal menyetujui KPI.', $e));
                }
            }
        }

        return back()->with('error', 'Status KPI tidak valid untuk diapprove saat ini.');
    }

    public function rejectKPI(Request $request, $kpi_daily_id)
    {
        $request->validate([
            'alasan_reject' => 'required|string',
        ]);

        $kpiDaily = KPIDaily::findOrFail($kpi_daily_id);

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
        $extra = KPIDailyExtra::findOrFail($id);
        $extra->update([
            'indikator_tambahan' => $request->indikator_tambahan,
            'catatan' => $request->catatan,
            'score' => $request->score,
        ]);

        return back()->with('success', 'KPI Tambahan berhasil diupdate.');
    }

    public function destroyExtra($id)
    {
        $extra = KPIDailyExtra::findOrFail($id);
        $extra->delete();

        return back()->with('success', 'KPI Tambahan berhasil dihapus.');
    }

    // ==========================================
    // Rekapitulasi & Report
    // ==========================================
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

    // ==========================================
    // Export Data & Generate Final Score
    // ==========================================
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

    // ==========================================
    // Report Performance Appraisal (PA)
    // ==========================================
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

    public function bulkApproveHR(Request $request)
    {
        $request->validate([
            'niks' => 'required|array|min:1',
            'bulan' => 'required',
            'tahun' => 'required',
        ], [
            'niks.required' => 'Pilih minimal satu karyawan untuk disetujui.',
        ]);

        $user = Auth::guard('user')->user();
        $hrIdentifier = (string) $user->id;

        [$tglAwal, $tglAkhir] = PeriodeKerja::bulan($request->bulan, $request->tahun)->range();

        DB::beginTransaction();
        try {
            // 1. Bulk Approve KPI Karyawan (Workbook)
            $updatedDaily = KPIDaily::whereIn('nik', $request->niks)
                ->whereBetween('tanggal', [$tglAwal, $tglAkhir])
                ->whereNull('approve_hr')
                // ->where('status', 'approved_by_atasan')
                ->update([
                    'status' => 'approved_by_hr',
                    'approve_hr' => $hrIdentifier,
                    'approve_hr_at' => now(),
                ]);

            // 2. Bulk Approve KPI Atasan
            $updatedAtasanDaily = KPIAtasanDaily::whereIn('nik', $request->niks)
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
