<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\Departemen;
use App\Models\Jabatan;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function usersIndex(Request $request)
    {
        $users = User::with(['roles', 'jabatan'])
            ->select('users.*', 'departemen.nama_dept', 'cabang.nama_cabang')
            ->leftJoin('departemen', 'users.kode_dept', '=', 'departemen.kode_dept')
            ->leftJoin('cabang', 'users.kode_cabang', '=', 'cabang.kode_cabang')
            ->when($request->name, function ($query, $name) {
                $query->where('users.name', 'ILIKE', "%{$name}%");
            })
            ->when($request->kode_dept, function ($query, $kode_dept) {
                $query->where('users.kode_dept', $kode_dept);
            })
            ->when($request->kode_cabang, function ($query, $kode_cabang) {
                $query->where('users.kode_cabang', $kode_cabang);
            })
            ->orderBy('users.name')
            ->paginate(25)
            ->withQueryString();

        $departemen = Departemen::orderBy('kode_dept')->get();
        $cabang = Cabang::orderBy('nama_cabang')->get();
        $jabatan = Jabatan::whereHas('role', function ($q) {
            $q->where('guard_name', 'user');
        })->orderBy('nama_jabatan')->get();

        return view('admin.users.index', compact(
            'users',
            'departemen',
            'jabatan',
            'cabang'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'kode_dept' => 'required',
            'jabatan_id' => 'required|exists:jabatan,id',
            'kode_cabang' => $this->kodeCabangRules($request),
        ]);

        DB::transaction(function () use ($request) {

            $jabatan = Jabatan::with('role')->findOrFail($request->jabatan_id);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'kode_dept' => $request->kode_dept,
                'kode_cabang' => $request->kode_cabang,
                'jabatan_id' => $jabatan->id,
            ]);

            if ($jabatan->role) {
                $user->assignRole($jabatan->role->name);
            }
        });

        return back()->with('success', 'Data user berhasil disimpan');
    }

    public function edit($id)
    {
        $user = User::with(['roles', 'jabatan'])->findOrFail($id);
        $departemen = Departemen::orderBy('kode_dept')->get();
        $cabang = Cabang::orderBy('nama_cabang')->get();
        $jabatan = Jabatan::whereHas('role', function ($q) {
            $q->where('guard_name', 'user');
        })->orderBy('nama_jabatan')->get();

        return view('admin.users.edit', compact(
            'user',
            'departemen',
            'jabatan',
            'cabang'
        ));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.$id,
            'kode_dept' => 'required',
            'jabatan_id' => 'required|exists:jabatan,id',
            'kode_cabang' => $this->kodeCabangRules($request),
            'password' => 'nullable|min:8',
        ]);

        DB::transaction(function () use ($request, $id) {

            $user = User::findOrFail($id);
            $jabatan = Jabatan::with('role')->findOrFail($request->jabatan_id);

            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'kode_dept' => $request->kode_dept,
                'kode_cabang' => $request->kode_cabang,
                'jabatan_id' => $jabatan->id,
            ]);

            if ($request->filled('password')) {
                $user->update([
                    'password' => bcrypt($request->password),
                ]);
            }

            if ($jabatan->role) {
                $user->syncRoles([$jabatan->role->name]);
            }
        });

        return back()->with('success', 'Data user berhasil diupdate');
    }

    /**
     * Admin cabang wajib punya cabang; tanpa cabang, pembatasan datanya tidak berlaku.
     */
    private function kodeCabangRules(Request $request): array
    {
        return [
            'nullable', 'string', 'max:8', 'exists:cabang,kode_cabang',
            Rule::requiredIf(fn () => Jabatan::with('role')->find($request->jabatan_id)?->role?->name === 'admin cabang'),
        ];
    }

    public function destroy($id)
    {
        try {
            User::findOrFail($id)->delete();

            return back()->with('success', 'Data user berhasil dihapus');
        } catch (QueryException $e) {
            return back()->with(
                'warning',
                'User tidak dapat dihapus karena masih memiliki relasi data'
            );
        }
    }

    // Edit current authenticated user (account settings)
    public function editSelf()
    {
        $user = auth('user')->user()->load('departemen', 'cabang');

        return view('admin.users.account', compact('user'));
    }

    // Update current authenticated user's account.
    // Departemen & cabang sengaja tidak bisa diubah sendiri: keduanya menentukan scope akses admin.
    public function updateSelf(Request $request)
    {
        $user = auth('user')->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'current_password' => 'required_with:password|current_password:user',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return back()->with('success', 'Akun berhasil diperbarui');
    }

    // Role Management
    public function rolesIndex()
    {
        return view('admin.users.role', [
            'roles' => Role::with('permissions')
                ->withCount('permissions')
                ->orderBy('permissions_count', 'desc')
                ->orderBy('id', 'asc')
                ->get(),
            'permissions' => Permission::all(),
        ]);
    }

    public function rolesStore(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'guard_name' => 'required|in:user,karyawan',
            'permissions' => 'array',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => $request->guard_name,
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return back()->with('success', 'Role berhasil ditambahkan');
    }

    public function rolesUpdate(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|unique:roles,name,'.$role->id,
            'permissions' => 'array',
        ]);

        $role->update(['name' => $request->name]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        } else {
            $role->syncPermissions([]);
        }

        return back()->with('success', 'Role berhasil diupdate');
    }

    public function rolesDestroy(Role $role)
    {
        $role->delete();

        return back()->with('success', 'Role berhasil dihapus');
    }

    // Permission Management
    public function permissionsIndex()
    {
        return view('admin.users.permission', [
            'permissions' => Permission::orderBy('guard_name', 'asc')->orderBy('name', 'asc')->get(),
        ]);
    }

    public function permissionsStore(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name',
            'guard_name' => 'required|in:user,karyawan', // Validasi guard
        ]);

        Permission::create([
            'name' => $request->name,
            'guard_name' => $request->guard_name,
        ]);

        return back()->with('success', 'Permission berhasil ditambahkan');
    }

    public function permissionsUpdate(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name,'.$permission->id,
            'guard_name' => 'required|in:user,karyawan', // Validasi guard
        ]);

        $permission->update([
            'name' => $request->name,
            'guard_name' => $request->guard_name,
        ]);

        return back()->with('success', 'Permission berhasil diupdate');
    }

    public function permissionsDestroy(Permission $permission)
    {
        $permission->delete();

        return back()->with('success', 'Permission berhasil dihapus');
    }
}
