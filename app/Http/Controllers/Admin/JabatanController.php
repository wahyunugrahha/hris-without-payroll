<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jabatan;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class JabatanController extends Controller
{
    public function index(Request $request)
    {
        $jabatan = Jabatan::with('role')
            ->when($request->nama_jabatan, function ($query, $nama_jabatan) {
                $query->where('nama_jabatan', 'ILIKE', '%'.$nama_jabatan.'%');
            })
            ->when($request->role_id, function ($query, $role_id) {
                $query->where('role_id', $role_id);
            })
            ->when($request->guard_name, function ($query, $guard_name) {
                $query->whereHas('role', function ($q) use ($guard_name) {
                    $q->where('guard_name', $guard_name);
                });
            })
            ->orderBy('role_id', 'asc')
            ->orderBy('nama_jabatan', 'asc')
            ->paginate(25)
            ->withQueryString();

        $roles = Role::orderBy('name')->get();

        return view('admin.jabatan.index', compact('jabatan', 'roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_jabatan' => 'required|string|max:100|unique:jabatan,nama_jabatan',
            'role_id' => 'required|exists:roles,id',
        ], [
            'nama_jabatan.unique' => 'Nama jabatan sudah ada.',
        ]);

        Jabatan::create($request->only(['nama_jabatan', 'role_id']));

        return redirect()->back()->with('success', 'Jabatan berhasil ditambahkan');
    }

    public function edit($id)
    {
        $jabatan = Jabatan::findOrFail($id);
        $roles = Role::all();

        return view('admin.jabatan.edit', compact('jabatan', 'roles'));
    }

    public function update(Request $request, $id)
    {
        $jabatan = Jabatan::findOrFail($id);

        $request->validate([
            'nama_jabatan' => 'required|string|max:100|unique:jabatan,nama_jabatan,'.$id.',id',
            'role_id' => 'required|exists:roles,id',
        ]);

        $jabatan->update([
            'nama_jabatan' => $request->nama_jabatan,
            'role_id' => $request->role_id,
        ]);

        return redirect()->route('jabatan.index')->with('success', 'Jabatan berhasil diupdate');
    }

    public function checkRelations($id)
    {
        try {
            $jabatan = Jabatan::findOrFail($id);

            return response()->json([
                'success' => true,
                'relations' => [
                    'karyawan' => $jabatan->karyawans()->count(),
                    'user' => $jabatan->users()->count(),
                    'kpi' => $jabatan->kpiMaster()->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $this->failMessage('Gagal memproses data.', $e),
            ], 500);
        }
    }

    public function delete($id)
    {
        try {
            $jabatan = Jabatan::findOrFail($id);
            $jabatan->delete();

            return back()->with('success', 'Jabatan berhasil dihapus');
        } catch (\Exception $e) {
            return back()->with('warning', $this->failMessage('Data Jabatan Gagal Dihapus.', $e));
        }
    }
}
