<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\CabangLokasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CabangLokasiController extends Controller
{
    /**
     * Memastikan user hanya boleh akses cabangnya sendiri jika role-nya admin cabang.
     */
    private function assertAuthorizedCabang(string $kodeCabang)
    {
        $user = Auth::guard('user')->user();

        // Security fix: Pastikan user login
        if (! $user) {
            abort(401);
        }

        if ($user->hasRole('admin cabang') && $user->kode_cabang !== $kodeCabang) {
            abort(403, 'Anda hanya bisa mengelola cabang Anda sendiri.');
        }
    }

    public function index(string $kode_cabang)
    {
        $this->assertAuthorizedCabang($kode_cabang);

        // Ambil data cabang induk
        $cabang = Cabang::findOrFail($kode_cabang);

        // Ambil daftar lokasi terkait cabang ini
        $lokasis = CabangLokasi::where('kode_cabang', $kode_cabang)
            ->orderByDesc('aktif')
            ->orderBy('nama_lokasi')
            ->get();

        // Payload untuk peta (LeafletJS)
        $lokasiPayload = $lokasis->map(function ($l) {
            return [
                'id' => $l->id,
                'lat' => (float) $l->latitude,
                'lon' => (float) $l->longitude,
                'radius' => (int) $l->radius,
                'nama' => $l->nama_lokasi,
                'aktif' => $l->aktif,
            ];
        })->toArray();

        return view('admin.cabang.lokasi.index', compact('cabang', 'lokasis', 'lokasiPayload'));
    }

    public function store(Request $request, string $kode_cabang)
    {
        $this->assertAuthorizedCabang($kode_cabang);
        Cabang::findOrFail($kode_cabang);

        $validated = $request->validate([
            'nama_lokasi' => ['required', 'string', 'max:100'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['required', 'integer', 'min:1'],
            'aktif' => ['sometimes'],
        ]);

        $validated['kode_cabang'] = $kode_cabang;
        // Fix: Gunakan 'has' atau boolean default false agar jika uncheck tetap tersimpan false
        $validated['aktif'] = $request->has('aktif') ? 1 : 0;

        CabangLokasi::create($validated);

        return back()->with('success', 'Lokasi titik absensi berhasil ditambahkan');
    }

    public function update(Request $request, string $kode_cabang, int $id)
    {
        $this->assertAuthorizedCabang($kode_cabang);

        $lokasi = CabangLokasi::where('kode_cabang', $kode_cabang)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'nama_lokasi' => ['required', 'string', 'max:100'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['required', 'integer', 'min:1'],
            'aktif' => ['sometimes'],
        ]);

        // Fix logic checkbox update
        $validated['aktif'] = $request->has('aktif') ? 1 : 0;

        $lokasi->update($validated);

        return back()->with('success', 'Lokasi titik absensi berhasil diperbarui');
    }

    public function destroy(string $kode_cabang, int $id)
    {
        $this->assertAuthorizedCabang($kode_cabang);

        $lokasi = CabangLokasi::where('kode_cabang', $kode_cabang)
            ->where('id', $id)
            ->firstOrFail();

        $lokasi->delete();

        return back()->with('success', 'Lokasi titik absensi berhasil dihapus');
    }
}
