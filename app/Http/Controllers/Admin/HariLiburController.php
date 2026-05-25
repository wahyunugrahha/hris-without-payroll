<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cabang;
use App\Models\Departemen;
use App\Models\HariLibur;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class HariLiburController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('user')->user();
        $isAdminCabang = $user && method_exists($user, 'hasRole') && $user->hasRole('admin cabang');
        $forcedKodeCabang = $isAdminCabang ? $user->kode_cabang : null;

        $query = HariLibur::query();

        if (!empty($forcedKodeCabang)) {
            $query->where(function ($q) use ($forcedKodeCabang) {
                $q->whereNull('kode_cabang')
                  ->orWhere('kode_cabang', $forcedKodeCabang);
            });
        }

        $query->selectRaw("
            MAX(id) as id, 
            tanggal_libur, 
            keterangan, 
            jenis_libur, 
            STRING_AGG(kode_cabang, ',') as kode_cabang,
            STRING_AGG(kode_dept, ',') as kode_dept
        ");

        $query->when($request->q, function ($q, $search) {
            $q->where(function($sub) use ($search) {
                $sub->where('keterangan', 'ilike', "%{$search}%");
            });
        });

        if ($request->filled('dari') && $request->filled('sampai')) {
            $query->whereBetween('tanggal_libur', [$request->dari, $request->sampai]);
        }

        $query->when($request->dari && $request->sampai, function ($q) use ($request) {
            $q->whereBetween('tanggal_libur', [$request->dari, $request->sampai]);
        });

        $query->when($request->jenis_libur, function ($q, $jenis) {
            $q->where('jenis_libur', $jenis);
        });

        $query->when($request->filled('kode_cabang'), function ($q) use ($request) {
            $q->where('kode_cabang', $request->kode_cabang);
        });

        $query->when($request->filled('kode_dept'), function ($q) use ($request) {
            $q->where('kode_dept', $request->kode_dept);
        });

        $query->groupBy('tanggal_libur', 'keterangan', 'jenis_libur')
            ->orderBy('tanggal_libur', 'desc');

        $hari_libur = $query->paginate(25)->withQueryString();

        $cabang = !empty($forcedKodeCabang)
            ? Cabang::where('kode_cabang', $forcedKodeCabang)->get()
            : Cabang::orderBy('nama_cabang')->get();
        $departemen = Departemen::orderBy('nama_dept')->get();
        
        return view('admin.harilibur.index', [
            'hari_libur'    => $hari_libur,
            'cabang'        => $cabang,
            'departemen'    => $departemen,
        ]);
    }

    public function create()
    {
        $cabang = Cabang::orderBy('nama_cabang')->get();
        $departemen = Departemen::orderBy('nama_dept')->get();
        return view('admin.harilibur.create', compact('cabang', 'departemen'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal_libur_dari'   => 'required|date',
            'tanggal_libur_sampai' => 'required|date|after_or_equal:tanggal_libur_dari',
            'keterangan'           => 'required|string',
            'jenis_libur'          => 'required',
            'kode_cabang'          => 'nullable|array',
            'kode_dept'            => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            // Normalisasi input: Jika kosong, set array berisi [null] agar loop tetap berjalan 1x
            $cabangList = !empty($request->kode_cabang) ? $request->kode_cabang : [null];
            $deptList   = !empty($request->kode_dept) ? $request->kode_dept : [null];

            // Parse tanggal menggunakan Carbon
            $startDate = Carbon::parse($request->tanggal_libur_dari);
            $endDate   = Carbon::parse($request->tanggal_libur_sampai);

            // 1. Loop per Hari (Dari tanggal awal hingga akhir)
            for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                
                $currentDate = $date->format('Y-m-d');

                // 2. Loop per Cabang
                foreach ($cabangList as $cbg) {
                    
                    // 3. Loop per Departemen
                    foreach ($deptList as $dept) {
                        HariLibur::create([
                            'tanggal_libur' => $currentDate,
                            'keterangan'    => $request->keterangan,
                            'jenis_libur'   => $request->jenis_libur,
                            'kode_cabang'   => $cbg,
                            'kode_dept'     => $dept
                        ]);
                    }
                }
            }

            DB::commit();
            return redirect()->route('harilibur.index')->with('success', 'Data Libur Berhasil Disimpan');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal Simpan: ' . $e->getMessage())->withInput();
        }
    }

    public function edit($id)
    {
        $master = HariLibur::findOrFail($id);

        // Ambil grup data yang identik untuk pre-fill checkbox
        $groupData = HariLibur::where([
            ['tanggal_libur', '=', $master->tanggal_libur],
            ['keterangan', '=', $master->keterangan],
            ['jenis_libur', '=', $master->jenis_libur],
        ])->get();

        return view('admin.harilibur.edit', [
            'master'         => $master,
            'selectedCabang' => $groupData->pluck('kode_cabang')->toArray(),
            'selectedDept'   => $groupData->pluck('kode_dept')->toArray(),
            'cabang'         => Cabang::orderBy('nama_cabang')->get(),
            'departemen'     => Departemen::orderBy('nama_dept')->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'tanggal_libur' => 'required|date',
            'keterangan'    => 'required|string',
            'jenis_libur'   => 'required',
        ]);

        DB::beginTransaction();
        try {
            $oldData = HariLibur::findOrFail($id);

            // 1. Hapus grup lama (Nuclear approach untuk sinkronisasi bersih)
            HariLibur::where([
                ['tanggal_libur', '=', $oldData->tanggal_libur],
                ['keterangan', '=', $oldData->keterangan],
                ['jenis_libur', '=', $oldData->jenis_libur],
            ])->delete();

            // 2. Insert data baru (Logika sama dengan store)
            $cabangList = !empty($request->kode_cabang) ? $request->kode_cabang : [null];
            $deptList   = !empty($request->kode_dept) ? $request->kode_dept : [null];

            foreach ($cabangList as $cbg) {
                foreach ($deptList as $dept) {
                    HariLibur::create([
                        'tanggal_libur' => $request->tanggal_libur,
                        'keterangan'    => $request->keterangan,
                        'jenis_libur'   => $request->jenis_libur,
                        'kode_cabang'   => $cbg,
                        'kode_dept'     => $dept
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('harilibur.index')->with('success', 'Data Berhasil Diperbarui');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal update: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $target = HariLibur::findOrFail($id);
            $namaLibur = $target->keterangan;

            // Hapus berdasarkan grup identik
            $deleted = HariLibur::where([
                ['tanggal_libur', '=', $target->tanggal_libur],
                ['keterangan', '=', $target->keterangan],
                ['jenis_libur', '=', $target->jenis_libur],
            ])->delete();

            DB::commit();
            return redirect()->route('harilibur.index')->with('success', "Data Libur:$namaLibur, Berhasil Dihapus");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }
}