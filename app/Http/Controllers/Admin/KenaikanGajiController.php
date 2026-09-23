<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\SalaryIncrease;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KenaikanGajiController extends Controller
{
    /**
     * Helper untuk mendapatkan Kode Cabang jika user adalah Admin Cabang
     */
    private function getForcedCabang()
    {
        $user = Auth::guard('user')->user();
        if ($user && $user->roles->pluck('name')->contains('admin cabang')) {
            return $user->kode_cabang;
        }

        return null;
    }

    public function index(Request $request)
    {
        $forcedKodeCabang = $this->getForcedCabang();

        $query = SalaryIncrease::query();
        $query->select('salary_increases.*', 'karyawan.nama_lengkap', 'karyawan.jabatan_id', 'karyawan.kode_dept', 'karyawan.kode_cabang')
            ->join('karyawan', 'salary_increases.nik', '=', 'karyawan.nik')
            ->orderBy('salary_increases.created_at', 'desc');

        // Security Filter: Jika admin cabang, batasi datanya
        if (! empty($forcedKodeCabang)) {
            $query->where('karyawan.kode_cabang', $forcedKodeCabang);
        }

        // Filter Periode Bulan & Tahun
        if ($request->filled('bulan')) {
            $query->whereMonth('tanggal_pengajuan', $request->bulan);
        }
        if ($request->filled('tahun')) {
            $query->whereYear('tanggal_pengajuan', $request->tahun);
        }

        // Filter Status
        if ($request->filled('status')) {
            $query->where('salary_increases.status', $request->status);
        }

        // Filter Nama atau NIK
        if ($request->filled('nama_karyawan')) {
            $query->where(function ($q) use ($request) {
                $q->where('karyawan.nama_lengkap', 'ilike', '%'.$request->nama_karyawan.'%')
                    ->orWhere('karyawan.nik', 'ilike', '%'.$request->nama_karyawan.'%');
            });
        }

        $pengajuan = $query->paginate(50);
        $pengajuan->appends($request->all());

        return view('admin.kenaikan_gaji.index', compact('pengajuan'));
    }

    public function approve($id)
    {
        $forcedKodeCabang = $this->getForcedCabang();

        $pengajuan = SalaryIncrease::select('salary_increases.*')
            ->join('karyawan', 'salary_increases.nik', '=', 'karyawan.nik')
            ->when(! empty($forcedKodeCabang), function ($q) use ($forcedKodeCabang) {
                return $q->where('karyawan.kode_cabang', $forcedKodeCabang);
            })
            ->where('salary_increases.id', $id)
            ->first();

        if ($pengajuan) {
            $pengajuan->status = 'approved';
            $pengajuan->approved_at = now();
            $pengajuan->approved_by = Auth::guard('user')->user()->name;
            $pengajuan->save();

            return redirect()->back()->with(['success' => 'Pengajuan Kenaikan Gaji berhasil disetujui']);
        }

        return redirect()->back()->with(['error' => 'Data pengajuan tidak ditemukan atau Anda tidak memiliki akses']);
    }

    public function reject(Request $request, $id)
    {
        $forcedKodeCabang = $this->getForcedCabang();

        $pengajuan = SalaryIncrease::select('salary_increases.*')
            ->join('karyawan', 'salary_increases.nik', '=', 'karyawan.nik')
            ->when(! empty($forcedKodeCabang), function ($q) use ($forcedKodeCabang) {
                return $q->where('karyawan.kode_cabang', $forcedKodeCabang);
            })
            ->where('salary_increases.id', $id)
            ->first();

        if ($pengajuan) {
            $pengajuan->status = 'rejected';
            $oldCatatan = $pengajuan->catatan ? 'Karyawan: '.$pengajuan->catatan.' | ' : '';
            $pengajuan->catatan = $oldCatatan.'Ditolak: '.$request->catatan;
            $pengajuan->save();

            return redirect()->back()->with(['success' => 'Pengajuan Kenaikan Gaji berhasil ditolak']);
        }

        return redirect()->back()->with(['error' => 'Data pengajuan tidak ditemukan atau Anda tidak memiliki akses']);
    }
}
