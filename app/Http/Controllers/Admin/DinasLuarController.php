<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\DinasLuar;
use App\Models\Jabatan;
use App\Models\Cabang;

class DinasLuarController extends Controller
{
    /**
     * Helper scope query.
     */
    private function getScopedQuery()
    {
        $user = Auth::guard('user')->user();
        $query = DinasLuar::query();

        if ($user && ($user->hasRole('admin cabang') || !empty($user->kode_cabang))) {
            $query->whereHas('karyawan', function ($q) use ($user) {
                $q->where('kode_cabang', $user->kode_cabang);
            });
        }

        return $query;
    }

    public function dashboard()
    {
        $baseQuery = $this->getScopedQuery();

        $menunggu = (clone $baseQuery)->where('status_acc', 'menunggu')->count();
        $acc = (clone $baseQuery)->where('status_acc', 'acc')->count();
        $tolak = (clone $baseQuery)->where('status_acc', 'tolak')->count();

        return view('admin.dinasluars.dashboard', compact('menunggu', 'acc', 'tolak'));
    }

    public function index(Request $request)
    {
        $dinasluars = $this->getScopedQuery()
            ->with(['karyawan.cabang', 'approver'])
            ->orderBy('created_at', 'desc')
            ->paginate(25)
            ->appends($request->all()); // PERBAIKAN: Agar filter tidak hilang saat ganti halaman

        return view('admin.dinasluars.index', compact('dinasluars'));
    }

    public function approval(Request $request)
    {
        $user = Auth::guard('user')->user();

        // PERBAIKAN: Definisi variabel untuk View
        $hasRoleAdminCabang = $user->hasRole('admin cabang');
        $isAdminCabang = !empty($user->kode_cabang); // Variabel ini sebelumnya hilang

        $query = $this->getScopedQuery()->with(['karyawan.cabang', 'approver']);

        // Filter Tanggal
        if ($request->filled('dari') && $request->filled('sampai')) {
            $dari = $request->dari;
            $sampai = $request->sampai;

            // Konversi format dd-mm-yyyy ke Y-m-d jika diperlukan
            if (strpos($dari, '-') !== false && strlen($dari) == 10) {
                $parts = explode('-', $dari);
                if (count($parts) == 3 && strlen($parts[2]) == 4) {
                    $dari = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
                }
            }
            if (strpos($sampai, '-') !== false && strlen($sampai) == 10) {
                $parts = explode('-', $sampai);
                if (count($parts) == 3 && strlen($parts[2]) == 4) {
                    $sampai = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
                }
            }

            $query->whereBetween('tgl_mulai', [$dari, $sampai]);
        }

        // Filter NIK
        if ($request->filled('nik')) {
            $query->whereHas('karyawan', function ($q) use ($request) {
                $q->where('nik', $request->nik);
            });
        }

        // Filter Nama
        if ($request->filled('nama_lengkap')) {
            $query->whereHas('karyawan', function ($q) use ($request) {
                // PERBAIKAN: Menggunakan ILIKE untuk PostgreSQL agar case-insensitive
                $q->where('nama_lengkap', 'ILIKE', '%' . $request->nama_lengkap . '%');
            });
        }

        // Filter Cabang
        if ($request->filled('kode_cabang')) {
            $query->whereHas('karyawan', function ($q) use ($request) {
                $q->where('kode_cabang', $request->kode_cabang);
            });
        }

        // Filter Status
        if (in_array($request->status_acc, ['menunggu', 'acc', 'tolak'], true)) {
            $query->where('status_acc', $request->status_acc);
        }

        $dinasluars = $query->orderByRaw("CASE WHEN status_acc = 'menunggu' THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc')
            ->paginate(25)
            ->appends($request->all());

        $cabangs = Cabang::orderBy('nama_cabang')->get();

        // PERBAIKAN: Menambahkan isAdminCabang ke compact
        return view('admin.dinasluars.approval', compact('dinasluars', 'hasRoleAdminCabang', 'isAdminCabang', 'cabangs'));
    }

    public function processAction(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:dinas_luar,id',
            'status_acc' => 'required|in:acc,tolak,menunggu',
            'catatan_approval' => 'nullable|string|max:255',
            'dana_disetujui' => 'nullable|numeric|min:0'
        ]);

        $dinasLuar = $this->getScopedQuery()->find($request->id);

        if (!$dinasLuar) {
            return redirect()->back()->with('error', 'Data tidak ditemukan atau Anda tidak memiliki akses.');
        }

        $isReset = $request->status_acc === 'menunggu';

        $updateData = [
            'status_acc' => $request->status_acc,
            'catatan_approval' => $isReset ? null : $request->catatan_approval,
            'approved_by' => $isReset ? null : Auth::guard('user')->id(),
            'approved_at' => $isReset ? null : now(),
        ];

        if ($request->status_acc === 'acc' && $request->filled('dana_disetujui')) {
            $updateData['dana_diajukan'] = $request->dana_disetujui;
        }

        $dinasLuar->update($updateData);

        $message = $isReset ? 'Status berhasil dikembalikan ke menunggu.' : 'Pengajuan berhasil diproses.';
        return redirect()->back()->with('success', $message);
    }

    public function quickAction($id, $action)
    {
        $dinasLuar = $this->getScopedQuery()->find($id);

        if (!$dinasLuar) {
            return redirect()->back()->with('error', 'Data tidak ditemukan.');
        }

        $status = ($action === 'approve') ? 'acc' : (($action === 'reject') ? 'tolak' : 'menunggu');

        $dinasLuar->update([
            'status_acc' => $status,
            'approved_by' => ($status === 'menunggu') ? null : Auth::guard('user')->id(),
            'approved_at' => ($status === 'menunggu') ? null : now(),
        ]);

        return redirect()->back()->with('success', "Data berhasil di-{$action}.");
    }

    public function approveOrReject(Request $request)
    {
        return $this->processAction($request);
    }

    public function cancel($id)
    {
        $dinasLuar = $this->getScopedQuery()->find($id);

        if (!$dinasLuar) {
            return redirect()->back()->with('error', 'Data tidak ditemukan atau akses ditolak.');
        }

        $dinasLuar->update([
            'status_acc' => 'menunggu',
            'catatan_approval' => null,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        return redirect()->back()->with('success', 'Status berhasil dikembalikan ke menunggu.');
    }

    /**
     * Cetak Form Perjalanan Dinas
     */
    public function cetak($id)
    {
        $dinasLuar = $this->getScopedQuery()
            ->with(['karyawan.cabang', 'approver.jabatan'])
            ->find($id);

        if (!$dinasLuar) {
            return redirect()->back()->with('error', 'Data tidak ditemukan atau akses ditolak.');
        }

        return view('admin.dinasluars.cetak', compact('dinasLuar'));
    }

    // Shortcut methods...
    public function approveDashboard($id)
    {
        return $this->quickAction($id, 'approve');
    }
    public function rejectDashboard($id)
    {
        return $this->quickAction($id, 'reject');
    }
}