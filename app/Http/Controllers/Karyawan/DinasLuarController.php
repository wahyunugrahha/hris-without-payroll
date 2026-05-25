<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\DinasLuar;

class DinasLuarController extends Controller
{

    /**
     * Catatan: Karyawan whitelist TETAP BISA mengajukan Dinas Luar.
     * Dinas Luar adalah status khusus terpisah dari presensi harian.
     * Routes dinas luar tidak diblokir oleh middleware wajub_presensi,
     * sehingga karyawan whitelist memiliki akses penuh ke fitur ini.
     */

    // Karyawan
    public function index(Request $request)
    {
        $nik = Auth::guard('karyawan')->user()->nik;

        $query = DinasLuar::where('nik', $nik);

        if (!empty($request->bulan)) {
            $query->whereMonth('tgl_mulai', $request->bulan);
        }

        if (!empty($request->tahun)) {
            $query->whereYear('tgl_mulai', $request->tahun);
        }

        $dinasluars = $query->orderBy('tgl_mulai', 'desc')->get();

        return view('karyawan.dinasluars.index', compact('dinasluars'));
    }

    public function create()
    {
        // Whitelist karyawan tetap bisa akses form pengajuan dinas luar
        return view('karyawan.dinasluars.create');
    }

    public function store(Request $request)
    {
        // Whitelist karyawan dapat menyimpan pengajuan dinas luar
        $request->validate([
            'tgl_mulai' => 'required|date',
            'tgl_selesai' => 'required|date|after_or_equal:tgl_mulai',
            'alasan' => 'required',
            'dasar_perjalanan' => 'required|string',
            'transportasi' => 'required|in:darat,laut,udara',
            'dana_diajukan' => 'required|numeric|min:0',
        ]);

        $nik = Auth::guard('karyawan')->user()->nik;

        DinasLuar::create([
            'nik' => $nik,
            'tgl_mulai' => $request->tgl_mulai,
            'tgl_selesai' => $request->tgl_selesai,
            'alasan' => $request->alasan,
            'dasar_perjalanan' => $request->dasar_perjalanan,
            'transportasi' => $request->transportasi,
            'dana_diajukan' => $request->dana_diajukan,
            'lokasi_tujuan' => $request->lokasi_tujuan,
            'keterangan' => $request->keterangan,
            'status_acc' => 'menunggu'
        ]);

        return redirect()->route('dinasluars.index')->with('success', 'Pengajuan berhasil dikirim.');
    }

    public function destroy($id)
    {
        $nik = Auth::guard('karyawan')->user()->nik;
        
        $dinasLuar = DinasLuar::where('id', $id)
            ->where('nik', $nik)
            ->first();

        if (!$dinasLuar) {
            return redirect()->route('dinasluars.index')->with('error', 'Data tidak ditemukan.');
        }

        // Hanya bisa dihapus jika belum di-approve
        if ($dinasLuar->status_acc === 'acc') {
            return redirect()->route('dinasluars.index')->with('error', 'Pengajuan yang sudah disetujui tidak dapat dihapus.');
        }

        $dinasLuar->delete();

        return redirect()->route('dinasluars.index')->with('success', 'Pengajuan berhasil dihapus.');
    }

    public function edit($id)
    {
        $nik = Auth::guard('karyawan')->user()->nik;
        
        $dinasLuar = DinasLuar::where('id', $id)
            ->where('nik', $nik)
            ->first();

        if (!$dinasLuar) {
            return redirect()->route('dinasluars.index')->with('error', 'Data tidak ditemukan.');
        }

        // Hanya bisa diedit jika belum di-approve
        if ($dinasLuar->status_acc === 'acc') {
            return redirect()->route('dinasluars.index')->with('error', 'Pengajuan yang sudah disetujui tidak dapat diedit.');
        }

        return view('karyawan.dinasluars.edit', compact('dinasLuar'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'tgl_mulai' => 'required|date',
            'tgl_selesai' => 'required|date|after_or_equal:tgl_mulai',
            'alasan' => 'required',
            'dasar_perjalanan' => 'required|string',
            'transportasi' => 'required|in:darat,laut,udara',
            'dana_diajukan' => 'required|numeric|min:0',
        ]);

        $nik = Auth::guard('karyawan')->user()->nik;
        
        $dinasLuar = DinasLuar::where('id', $id)
            ->where('nik', $nik)
            ->first();

        if (!$dinasLuar) {
            return redirect()->route('dinasluars.index')->with('error', 'Data tidak ditemukan.');
        }

        // Hanya bisa diedit jika belum di-approve
        if ($dinasLuar->status_acc === 'acc') {
            return redirect()->route('dinasluars.index')->with('error', 'Pengajuan yang sudah disetujui tidak dapat diedit.');
        }

        $dinasLuar->update([
            'tgl_mulai' => $request->tgl_mulai,
            'tgl_selesai' => $request->tgl_selesai,
            'alasan' => $request->alasan,
            'dasar_perjalanan' => $request->dasar_perjalanan,
            'transportasi' => $request->transportasi,
            'dana_diajukan' => $request->dana_diajukan,
            'lokasi_tujuan' => $request->lokasi_tujuan,
            'keterangan' => $request->keterangan,
        ]);

        return redirect()->route('dinasluars.index')->with('success', 'Pengajuan berhasil diperbarui.');
    }
}