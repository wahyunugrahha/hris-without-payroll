<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\DinasLuar;
use App\Support\JumlahPerStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DinasLuarController extends Controller
{
    /**
     * Daftar dinas luar admin ada di halaman approval (dengan filter status).
     */
    public function index()
    {
        return redirect()->route('dinasluars.approval');
    }

    public function approval(Request $request)
    {
        $user = Auth::guard('user')->user();

        // PERBAIKAN: Definisi variabel untuk View
        $hasRoleAdminCabang = $user->isAdminCabang();
        $isAdminCabang = ! empty($user->kode_cabang); // Variabel ini sebelumnya hilang

        $query = DinasLuar::visibleTo(Auth::guard('user')->user())->with(['karyawan.cabang', 'approver']);

        // Filter Tanggal
        if ($request->filled('dari') && $request->filled('sampai')) {
            $dari = $request->dari;
            $sampai = $request->sampai;

            // Konversi format dd-mm-yyyy ke Y-m-d jika diperlukan
            if (strpos($dari, '-') !== false && strlen($dari) == 10) {
                $parts = explode('-', $dari);
                if (count($parts) == 3 && strlen($parts[2]) == 4) {
                    $dari = $parts[2].'-'.$parts[1].'-'.$parts[0];
                }
            }
            if (strpos($sampai, '-') !== false && strlen($sampai) == 10) {
                $parts = explode('-', $sampai);
                if (count($parts) == 3 && strlen($parts[2]) == 4) {
                    $sampai = $parts[2].'-'.$parts[1].'-'.$parts[0];
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
                $q->where('nama_lengkap', 'ILIKE', '%'.$request->nama_lengkap.'%');
            });
        }

        // Filter Cabang
        if ($request->filled('kode_cabang')) {
            $query->whereHas('karyawan', function ($q) use ($request) {
                $q->where('kode_cabang', $request->kode_cabang);
            });
        }

        // Jumlah per status untuk tab: bulan berjalan, atau rentang tanggal bila difilter.
        $adaRentang = $request->filled('dari') && $request->filled('sampai');
        $jumlahStatus = JumlahPerStatus::bulanan($query, 'status_acc', $adaRentang ? null : 'tgl_mulai', now());
        $periodeJumlah = $adaRentang ? 'rentang tanggal terpilih' : now()->translatedFormat('F Y');

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
        return view('admin.dinasluars.approval', compact('dinasluars', 'hasRoleAdminCabang', 'isAdminCabang', 'cabangs', 'jumlahStatus', 'periodeJumlah'));
    }

    public function processAction(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:dinas_luar,id',
            'status_acc' => 'required|in:acc,tolak,menunggu',
            'catatan_approval' => 'nullable|string|max:255',
            'dana_disetujui' => 'nullable|numeric|min:0',
        ]);

        $dinasLuar = DinasLuar::visibleTo(Auth::guard('user')->user())->find($request->id);

        if (! $dinasLuar) {
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

    public function approveOrReject(Request $request)
    {
        return $this->processAction($request);
    }

    public function cancel($id)
    {
        $dinasLuar = DinasLuar::visibleTo(Auth::guard('user')->user())->find($id);

        if (! $dinasLuar) {
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
        $dinasLuar = DinasLuar::visibleTo(Auth::guard('user')->user())
            ->with(['karyawan.cabang', 'approver.jabatan'])
            ->find($id);

        if (! $dinasLuar) {
            return redirect()->back()->with('error', 'Data tidak ditemukan atau akses ditolak.');
        }

        return view('admin.dinasluars.cetak', compact('dinasLuar'));
    }
}
