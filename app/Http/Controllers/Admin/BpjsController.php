<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BpjsRequest;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BpjsController extends Controller
{
    private function getForcedCabang(): ?string
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

        $query = BpjsRequest::query()
            ->select('bpjs_tk_requests.*', 'karyawan.nama_lengkap', 'karyawan.kode_cabang')
            ->join('karyawan', 'bpjs_tk_requests.nik', '=', 'karyawan.nik')
            ->orderByDesc('bpjs_tk_requests.created_at');

        if (! empty($forcedKodeCabang)) {
            $query->where('karyawan.kode_cabang', $forcedKodeCabang);
        }

        if (! empty($request->dari) && ! empty($request->sampai)) {
            $query->whereBetween('bpjs_tk_requests.created_at', [
                $request->dari.' 00:00:00',
                $request->sampai.' 23:59:59',
            ]);
        }

        if (! empty($request->nik)) {
            $query->where('bpjs_tk_requests.nik', $request->nik);
        }

        if (! empty($request->nama_lengkap)) {
            $query->where('karyawan.nama_lengkap', 'like', '%'.$request->nama_lengkap.'%');
        }

        if (! empty($request->status)) {
            $query->where('bpjs_tk_requests.status', $request->status);
        }

        $pengajuan = $query->paginate(15);
        $pengajuan->appends($request->all());

        return view('admin.bpjs.index', compact('pengajuan'));
    }

    public function show($id)
    {
        $forcedKodeCabang = $this->getForcedCabang();

        $pengajuan = BpjsRequest::with('karyawan')->findOrFail($id);
        $karyawan = Karyawan::findOrFail($pengajuan->nik);

        if (! empty($forcedKodeCabang) && $karyawan->kode_cabang !== $forcedKodeCabang) {
            abort(403, 'Anda tidak memiliki akses ke data ini.');
        }

        return view('admin.bpjs.show', compact('pengajuan', 'karyawan'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'no_bpjs_kesehatan' => 'nullable|string|max:255',
            'no_bpjs_ketenagakerjaan' => 'nullable|string|max:255',
            'foto_bpjs_kesehatan' => 'nullable|image|mimes:jpg,jpeg,png|max:3072',
            'foto_bpjs_ketenagakerjaan' => 'nullable|image|mimes:jpg,jpeg,png|max:3072',
            'catatan' => 'nullable|string',
        ]);

        $forcedKodeCabang = $this->getForcedCabang();

        $pengajuan = BpjsRequest::findOrFail($id);

        $karyawan = Karyawan::findOrFail($pengajuan->nik);

        if (! empty($forcedKodeCabang) && $karyawan->kode_cabang !== $forcedKodeCabang) {
            abort(403, 'Anda tidak memiliki akses ke data ini.');
        }

        $dataKaryawan = [
            'no_bpjs_kesehatan' => $request->no_bpjs_kesehatan,
            'no_bpjs_ketenagakerjaan' => $request->no_bpjs_ketenagakerjaan,
        ];

        if ($request->hasFile('foto_bpjs_kesehatan')) {
            $fileKesehatan = $request->file('foto_bpjs_kesehatan');
            $namaFotoKesehatan = $karyawan->nik.'_bpjs_kes_'.time().'.'.$fileKesehatan->extension();

            if (! empty($karyawan->foto_bpjs_kesehatan) && Storage::disk('public')->exists('uploads/karyawan/bpjs/'.$karyawan->foto_bpjs_kesehatan)) {
                Storage::disk('public')->delete('uploads/karyawan/bpjs/'.$karyawan->foto_bpjs_kesehatan);
            }

            $fileKesehatan->storeAs('uploads/karyawan/bpjs/', $namaFotoKesehatan, 'public');
            $dataKaryawan['foto_bpjs_kesehatan'] = $namaFotoKesehatan;
        }

        if ($request->hasFile('foto_bpjs_ketenagakerjaan')) {
            $fileKetenagakerjaan = $request->file('foto_bpjs_ketenagakerjaan');
            $namaFotoKetenagakerjaan = $karyawan->nik.'_bpjs_ket_'.time().'.'.$fileKetenagakerjaan->extension();

            if (! empty($karyawan->foto_bpjs_ketenagakerjaan) && Storage::disk('public')->exists('uploads/karyawan/bpjs/'.$karyawan->foto_bpjs_ketenagakerjaan)) {
                Storage::disk('public')->delete('uploads/karyawan/bpjs/'.$karyawan->foto_bpjs_ketenagakerjaan);
            }

            $fileKetenagakerjaan->storeAs('uploads/karyawan/bpjs/', $namaFotoKetenagakerjaan, 'public');
            $dataKaryawan['foto_bpjs_ketenagakerjaan'] = $namaFotoKetenagakerjaan;
        }

        $karyawan->update($dataKaryawan);

        $pengajuan->update([
            'status' => 'processed',
            'processed_at' => now(),
            'processed_by' => Auth::guard('user')->user()->name ?? 'System',
            'catatan' => $request->catatan,
        ]);

        return redirect()->route('admin.bpjs.index')->with('success', 'Data BPJS karyawan berhasil diperbarui.');
    }
}
