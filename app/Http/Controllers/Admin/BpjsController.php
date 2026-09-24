<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BpjsRequest;
use App\Models\Karyawan;
use App\Services\FotoKaryawanService;
use App\Support\JumlahPerStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BpjsController extends Controller
{
    public function index(Request $request)
    {
        $forcedKodeCabang = $this->scopedCabang();

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
            // Satu kolom cari untuk nama atau NIK (tidak peka huruf besar/kecil).
            $cari = '%'.$request->nama_lengkap.'%';
            $query->where(fn ($q) => $q->where('karyawan.nama_lengkap', 'ilike', $cari)
                ->orWhere('bpjs_tk_requests.nik', 'ilike', $cari));
        }

        // Jumlah per status untuk tab: rentang yang difilter, atau bulan berjalan.
        $adaRentang = ! empty($request->dari) && ! empty($request->sampai);
        $jumlahStatus = JumlahPerStatus::bulanan($query, 'bpjs_tk_requests.status', $adaRentang ? null : 'bpjs_tk_requests.created_at', now());
        $periodeJumlah = $adaRentang ? 'rentang tanggal terpilih' : now()->translatedFormat('F Y');

        if (! empty($request->status)) {
            $query->where('bpjs_tk_requests.status', $request->status);
        }

        $pengajuan = $query->paginate(15);
        $pengajuan->appends($request->all());

        return view('admin.bpjs.index', compact('pengajuan', 'jumlahStatus', 'periodeJumlah'));
    }

    public function show($id)
    {
        $forcedKodeCabang = $this->scopedCabang();

        $pengajuan = BpjsRequest::with('karyawan')->findOrFail($id);
        $karyawan = Karyawan::findOrFail($pengajuan->nik);

        if (! empty($forcedKodeCabang) && $karyawan->kode_cabang !== $forcedKodeCabang) {
            abort(403, 'Anda tidak memiliki akses ke data ini.');
        }

        return view('admin.bpjs.show', compact('pengajuan', 'karyawan'));
    }

    public function update(Request $request, $id, FotoKaryawanService $foto)
    {
        $request->validate([
            'no_bpjs_kesehatan' => 'nullable|string|max:255',
            'no_bpjs_ketenagakerjaan' => 'nullable|string|max:255',
            'foto_bpjs_kesehatan' => 'nullable|image|mimes:jpg,jpeg,png|max:3072',
            'foto_bpjs_ketenagakerjaan' => 'nullable|image|mimes:jpg,jpeg,png|max:3072',
            'catatan' => 'nullable|string',
        ]);

        $forcedKodeCabang = $this->scopedCabang();

        $pengajuan = BpjsRequest::findOrFail($id);

        $karyawan = Karyawan::findOrFail($pengajuan->nik);

        if (! empty($forcedKodeCabang) && $karyawan->kode_cabang !== $forcedKodeCabang) {
            abort(403, 'Anda tidak memiliki akses ke data ini.');
        }

        $dataKaryawan = [
            'no_bpjs_kesehatan' => $request->no_bpjs_kesehatan,
            'no_bpjs_ketenagakerjaan' => $request->no_bpjs_ketenagakerjaan,
        ];

        foreach (['foto_bpjs_kesehatan', 'foto_bpjs_ketenagakerjaan'] as $jenis) {
            if ($request->hasFile($jenis)) {
                $dataKaryawan[$jenis] = $foto->ganti($jenis, $request->file($jenis), $karyawan->{$jenis}, $karyawan->nik);
            }
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
