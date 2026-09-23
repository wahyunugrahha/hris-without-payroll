<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

use App\Models\MasterCuti;

class CutiController extends Controller
{
    public function index(Request $request)
    {
        $cuti = MasterCuti::query()
            ->select('kode_cuti', 'nama_cuti', 'jml_hari')

            ->when($request->nama_cuti, function ($query, $nama) {
                $query->whereRaw(
                    'LOWER(nama_cuti) ILIKE ?',
                    ['%' . strtolower($nama) . '%']
                );
            })

            ->orderBy('nama_cuti', 'ASC')
            ->paginate(10)
            ->withQueryString();

        return view('admin.cuti.index', compact('cuti'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode_cuti' => [
                'required',
                'string',
                'size:3',
                'unique:master_cuti,kode_cuti',
                'regex:/^[A-Z0-9]{3}$/'
            ],
            'nama_cuti' => 'required|string|max:30',
            'jml_hari'  => 'required|integer|min:1|max:365',
        ], [
            'kode_cuti.required' => 'Kode Cuti wajib diisi.',
            'kode_cuti.size'     => 'Kode Cuti harus 3 karakter.',
            'kode_cuti.unique'   => 'Kode Cuti sudah terdaftar.',
            'kode_cuti.regex'    => 'Kode Cuti harus huruf besar/angka.',
            'nama_cuti.required' => 'Nama Cuti wajib diisi.',
            'jml_hari.required'  => 'Jumlah Hari wajib diisi.',
        ]);

        MasterCuti::create([
            'kode_cuti' => strtoupper($request->kode_cuti),
            'nama_cuti' => $request->nama_cuti,
            'jml_hari'  => $request->jml_hari,
        ]);

        return back()->with('success', 'Data Cuti Berhasil Disimpan');
    }

    public function edit($kode_cuti)
    {
        $cuti = MasterCuti::findOrFail($kode_cuti);
        return view('admin.cuti.edit', compact('cuti'));
    }

    public function update(Request $request, $kode_cuti)
    {
        $request->validate([
            'kode_cuti_edit' => 'required|string|size:3|unique:master_cuti,kode_cuti,' . $kode_cuti . ',kode_cuti|regex:/^[A-Z0-9]{3}$/',
            'nama_cuti_edit' => 'required|string|max:30',
            'jml_hari_edit'  => 'required|integer|min:1|max:365',
        ], [
            'kode_cuti_edit.unique' => 'Kode Cuti sudah digunakan.',
            'kode_cuti_edit.size' => 'Kode Cuti harus tepat 3 karakter.',
            'kode_cuti_edit.regex' => 'Kode Cuti harus berupa 3 karakter angka atau huruf kapital.',
        ]);

        $new_kode_cuti = strtoupper($request->kode_cuti_edit);

        try {
            $cutiLama = \App\Models\MasterCuti::where('kode_cuti', $kode_cuti)->first();
            // Update menggunakan DB query builder untuk menangani perubahan Primary Key
            $update = \DB::table('master_cuti')->where('kode_cuti', $kode_cuti)->update([
                'kode_cuti' => $new_kode_cuti,
                'nama_cuti' => $request->nama_cuti_edit,
                'jml_hari'  => $request->jml_hari_edit,
            ]);

            return Redirect::back()->with('success', 'Data Cuti Berhasil Diupdate');
        } catch (\Exception $e) {
            return Redirect::back()->with('warning', $this->failMessage('Data Cuti Gagal Diupdate.', $e))->withInput();
        }
    }

    public function checkRelations($kode_cuti)
    {
        try {
            $cuti = MasterCuti::findOrFail($kode_cuti);

            return response()->json([
                'success' => true,
                'relations' => [
                    'izin' => $cuti->izins()->count(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $this->failMessage('Gagal memproses data.', $e)
            ], 500);
        }
    }

    public function destroy($kode_cuti)
    {
        try {
            $cuti = MasterCuti::findOrFail($kode_cuti);
            $cuti->delete();

            return Redirect::back()->with('success', 'Data Cuti Berhasil Dihapus');
        } catch (\Exception $e) {
            return Redirect::back()->with('warning', $this->failMessage('Gagal memproses data.', $e));
        }
    }
}