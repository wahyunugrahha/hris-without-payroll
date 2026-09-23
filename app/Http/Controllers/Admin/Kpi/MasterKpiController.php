<?php

namespace App\Http\Controllers\Admin\Kpi;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\Departemen;
use App\Models\Jabatan;
use App\Models\KPIMaster;
use App\Models\KPIMasterAtasan;
use App\Models\KPIMasterDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Master KPI per jabatan/departemen/cabang beserta indikatornya.
 */
class MasterKpiController extends Controller
{
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
}
