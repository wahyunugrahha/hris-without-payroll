<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

use App\Models\Cabang;
use App\Models\CabangLokasi;

class CabangController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('user')->user();
        $isAdminCabang = $user?->hasRole('admin cabang');
        $forcedKodeCabang = $isAdminCabang ? $user->kode_cabang : null;

        $cabang = Cabang::query()
            ->select([
                'kode_cabang',
                'nama_cabang',
                'lokasi_kantor',
                'radius',
            ])

            ->when($request->nama_cabang, function ($query, $nama) {
                $query->whereRaw('LOWER(nama_cabang) ILIKE ?', ['%' . strtolower($nama) . '%']);
            })

            ->when($forcedKodeCabang, function ($query, $kode) {
                $query->where('kode_cabang', $kode);
            })

            ->orderBy('nama_cabang', 'ASC')
            ->paginate(10)
            ->withQueryString();

        return view('admin.cabang.index', compact('cabang'));
    }

    public function store(Request $request)
    {
        $user = Auth::guard('user')->user();

        if ($user?->hasRole('admin cabang')) {
            return back()->with('warning', 'Admin cabang tidak diizinkan menambah cabang baru.');
        }

        $request->validate([
            'kode_cabang'   => ['required','string','min:3','max:8','unique:cabang,kode_cabang','regex:/^[A-Z0-9]{3,8}$/'],
            'nama_cabang'   => 'required|string|max:50',
            'lokasi_kantor' => 'required|string|max:255',
            'radius'        => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($request) {
            $kode = strtoupper($request->kode_cabang);

            // 1) Simpan master cabang
            Cabang::create([
                'kode_cabang'   => $kode,
                'nama_cabang'   => $request->nama_cabang,
                'lokasi_kantor' => $request->lokasi_kantor,
                'radius'        => $request->radius,
            ]);

            // 2) Turunkan koordinat awal ke multilokasi jika ada koordinat valid
            //    Format yang diterima: "lat,long" atau "lat,long,alamat" (ambil 2 pertama)
            $lat = null; $lon = null;
            $parts = array_map('trim', explode(',', (string) $request->lokasi_kantor));
            if (count($parts) >= 2) {
                $latCandidate = is_numeric($parts[0]) ? (float) $parts[0] : null;
                $lonCandidate = is_numeric($parts[1]) ? (float) $parts[1] : null;
                if ($latCandidate !== null && $lonCandidate !== null && $latCandidate >= -90 && $latCandidate <= 90 && $lonCandidate >= -180 && $lonCandidate <= 180) {
                    $lat = $latCandidate;
                    $lon = $lonCandidate;
                }
            }

            if ($lat !== null && $lon !== null) {
                CabangLokasi::create([
                    'kode_cabang' => $kode,
                    'nama_lokasi' => 'Kantor',
                    'latitude'    => $lat,
                    'longitude'   => $lon,
                    'radius'      => (int) $request->radius,
                    'aktif'       => true,
                ]);
            }
        });

        return back()->with('success', 'Data Cabang Berhasil Disimpan');
    }

    public function edit($kode_cabang)
    {
        $user = Auth::guard('user')->user();

        if ($user?->hasRole('admin cabang') && $user->kode_cabang !== $kode_cabang) {
            return back()->with('warning', 'Anda hanya bisa mengelola cabang Anda sendiri.');
        }

        $cabang = Cabang::findOrFail($kode_cabang);

        return view('admin.cabang.edit', compact('cabang'));
    }

    public function update(Request $request, $kode_cabang)
    {
        $user = Auth::guard('user')->user();

        if ($user?->hasRole('admin cabang') && $user->kode_cabang !== $kode_cabang) {
            return back()->with('warning', 'Anda hanya bisa mengelola cabang Anda sendiri.');
        }

        $request->validate([
            'kode_cabang_edit'   => ['required','string','min:3','max:8','regex:/^[A-Z0-9]{3,8}$/','unique:cabang,kode_cabang,' . $kode_cabang . ',kode_cabang'],
            'nama_cabang_edit'   => 'required|string|max:50',
            'lokasi_kantor_edit' => 'required|string|max:255',
            'radius_edit'        => 'required|integer|min:1',
        ], [
            'kode_cabang_edit.unique' => 'Kode Cabang sudah digunakan oleh cabang lain.',
            'kode_cabang_edit.regex' => 'Kode Cabang harus berupa 3-8 karakter angka atau huruf kapital.',
        ]);

        $new_kode_cabang = strtoupper($request->kode_cabang_edit);

        try {
            DB::transaction(function () use ($request, $kode_cabang, $new_kode_cabang) {
                $cabangLama = Cabang::where('kode_cabang', $kode_cabang)->first();
                // 1) Update master cabang (menggunakan query builder untuk PK change)
                DB::table('cabang')->where('kode_cabang', $kode_cabang)->update([
                    'kode_cabang'   => $new_kode_cabang,
                    'nama_cabang'   => $request->nama_cabang_edit,
                    'lokasi_kantor' => $request->lokasi_kantor_edit,
                    'radius'        => $request->radius_edit,
                ]);

                $cabangBaru = Cabang::where('kode_cabang', $new_kode_cabang)->first();

                // 2) Sinkronkan ke multilokasi jika relevan (Gunakan kode baru karena cascade)
                $lat = null; $lon = null;
                $parts = array_map('trim', explode(',', (string) $request->lokasi_kantor_edit));
                if (count($parts) >= 2) {
                    $latCandidate = is_numeric($parts[0]) ? (float) $parts[0] : null;
                    $lonCandidate = is_numeric($parts[1]) ? (float) $parts[1] : null;
                    if ($latCandidate !== null && $lonCandidate !== null && $latCandidate >= -90 && $latCandidate <= 90 && $lonCandidate >= -180 && $lonCandidate <= 180) {
                        $lat = $latCandidate;
                        $lon = $lonCandidate;
                    }
                }

                if ($lat !== null && $lon !== null) {
                    $count = CabangLokasi::where('kode_cabang', $new_kode_cabang)->count();
                    if ($count === 0) {
                        CabangLokasi::create([
                            'kode_cabang' => $new_kode_cabang,
                            'nama_lokasi' => 'Kantor',
                            'latitude'    => $lat,
                            'longitude'   => $lon,
                            'radius'      => (int) $request->radius_edit,
                            'aktif'       => true,
                        ]);
                    } elseif ($count === 1) {
                        $loc = CabangLokasi::where('kode_cabang', $new_kode_cabang)->first();
                        if ($loc && ($loc->nama_lokasi === 'Kantor')) {
                            $loc->update([
                                'latitude'  => $lat,
                                'longitude' => $lon,
                                'radius'    => (int) $request->radius_edit,
                                'aktif'     => true,
                            ]);
                        }
                    }
                }
            });

            return Redirect::back()->with('success', 'Data Cabang Berhasil Diupdate');
        } catch (\Exception $e) {
            return Redirect::back()->with('warning', $this->failMessage('Data Cabang Gagal Diupdate.', $e))->withInput();
        }
    }

    public function checkRelations($kode_cabang)
    {
        try {
            $cabang = Cabang::findOrFail($kode_cabang);

            return response()->json([
                'success' => true,
                'relations' => [
                    'karyawan' => $cabang->karyawans()->count(),
                    'user' => $cabang->users()->count(),
                    'kpi' => $cabang->kpi()->count(),
                    'configuration' => $cabang->jkDepts()->count(),
                    'holiday' => $cabang->holidays()->count(),
                    'location' => $cabang->lokasis()->count(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $this->failMessage('Gagal memproses data.', $e)
            ], 500);
        }
    }

    public function destroy($kode_cabang)
    {
        try {
            $cabang = Cabang::findOrFail($kode_cabang);
            $user = Auth::guard('user')->user();

            if ($user?->hasRole('admin cabang') && $user->kode_cabang !== $kode_cabang) {
                return back()->with('warning', 'Anda hanya bisa menghapus cabang Anda sendiri.');
            }

            $cabang->delete();
            return back()->with('success', 'Data Cabang Berhasil Dihapus');

        } catch (\Exception $e) {
            return back()->with('warning', $this->failMessage('Data Cabang Gagal Dihapus.', $e));
        }
    }
}