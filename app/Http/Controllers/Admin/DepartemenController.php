<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;

use App\Models\Departemen;

class DepartemenController extends Controller
{
    public function index(Request $request)
    {
        $departemen = Departemen::query()
            ->select(
                'kode_dept',
                'nama_dept',
                DB::raw('UPPER(nama_dept) as nama_dept_upper')
            )
            ->when($request->filled('nama_dept'), function ($query) use ($request) {
                $query->whereRaw(
                    'LOWER(nama_dept) ILIKE ?',
                    ['%' . strtolower($request->nama_dept) . '%']
                );
            })
            ->orderBy('nama_dept', 'ASC')
            ->paginate(25);

        return view('admin.departemen.index', compact('departemen'));
    }

    public function store(Request $request)
    {
        $kode_dept_regex = 'regex:/^[A-Z0-9]{3}$/';

        try {
            $request->validate([
                'kode_dept' => 'required|string|size:3|unique:departemen,kode_dept|' . $kode_dept_regex,
                'nama_dept' => 'required|string|max:100',
            ]);
        } catch (ValidationException $e) {
            return Redirect::back()->withErrors($e->errors())->withInput();
        }

        try {
            Departemen::create([
                'kode_dept' => strtoupper($request->kode_dept),
                'nama_dept' => $request->nama_dept,
            ]);

            return Redirect::back()->with('success', 'Data Departemen Berhasil Disimpan');
        } catch (\Exception $e) {
            return Redirect::back()->with('warning', 'Data Departemen Gagal Disimpan')->withInput();
        }
    }

    public function edit($kode_dept)
    {
        $departemen = Departemen::findOrFail($kode_dept);

        return view('admin.departemen.edit', compact('departemen'));
    }

    public function update(Request $request, $kode_dept)
    {
        $kode_dept_regex = 'regex:/^[A-Z0-9]{3}$/';

        try {
            $request->validate([
                'kode_dept_edit' => 'required|string|size:3|' . $kode_dept_regex . '|unique:departemen,kode_dept,' . $kode_dept . ',kode_dept',
                'nama_dept_edit' => 'required|string|max:100',
            ], [
                'kode_dept_edit.unique' => 'Kode Departemen sudah digunakan oleh departemen lain.',
                'kode_dept_edit.size' => 'Kode Departemen harus tepat 3 karakter.',
                'kode_dept_edit.regex' => 'Kode Departemen harus berupa 3 karakter angka atau huruf kapital.',
                'nama_dept_edit.required' => 'Nama Departemen tidak boleh kosong.',
            ]);
        } catch (ValidationException $e) {
            return Redirect::back()->withErrors($e->errors())->withInput();
        }

        try {
            $new_kode_dept = strtoupper($request->kode_dept_edit);
            $nama_dept = $request->nama_dept_edit;

            // Cari data lama
            $departemen = Departemen::where('kode_dept', $kode_dept)->firstOrFail();

            // Cek apakah ada perubahan
            if ($departemen->kode_dept === $new_kode_dept && $departemen->nama_dept === $nama_dept) {
                return Redirect::back()->with('warning', 'Tidak ada perubahan pada data.');
            }

            // Update menggunakan query builder untuk menangani perubahan Primary Key
            Departemen::where('kode_dept', $kode_dept)->update([
                'kode_dept' => $new_kode_dept,
                'nama_dept' => $nama_dept
            ]);

            return Redirect::back()->with('success', 'Data Departemen Berhasil Diupdate');
        } catch (\Exception $e) {
            return Redirect::back()->with('warning', $this->failMessage('Data Departemen Gagal Diupdate.', $e))->withInput();
        }
    }

    public function checkRelations($kode_dept)
    {
        try {
            $departemen = Departemen::findOrFail($kode_dept);

            return response()->json([
                'success' => true,
                'relations' => [
                    'karyawan' => $departemen->karyawans()->count(),
                    'user' => $departemen->users()->count(),
                    'kpi' => $departemen->kpi()->count(),
                    'configuration' => $departemen->configurations()->count(),
                    'holiday' => $departemen->holidays()->count(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $this->failMessage('Gagal memproses data.', $e)
            ], 500);
        }
    }

    public function destroy($kode_dept)
    {
        try {
            $departemen = Departemen::findOrFail($kode_dept);
            $departemen->delete();

            return Redirect::back()->with('success', 'Data Departemen Berhasil Dihapus');
        } catch (\Exception $e) {
            return Redirect::back()->with('warning', $this->failMessage('Gagal memproses data.', $e));
        }
    }
}
