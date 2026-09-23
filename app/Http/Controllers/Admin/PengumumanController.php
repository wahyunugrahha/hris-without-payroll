<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengumuman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

class PengumumanController extends Controller
{
    // --- ADMIN SIDE --- //
    public function index()
    {
        $pengumuman = Pengumuman::orderBy('created_at', 'desc')->get();

        // Pastikan Anda punya view admin: resources/views/pengumuman/index.blade.php
        return view('admin.pengumuman.index', compact('pengumuman'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'gambar' => 'nullable|image|max:10240', // Max 2MB
        ]);

        $gambarPath = null;
        if ($request->hasFile('gambar')) {
            $gambarPath = $request->file('gambar')->store('uploads/pengumuman', 'public');
        }

        Pengumuman::create([
            'judul' => $request->judul,
            'isi' => $request->isi,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'gambar' => $gambarPath,
            'is_active' => 1,
        ]);

        return Redirect::back()->with(['success' => 'Pengumuman Berhasil Disimpan']);
    }

    public function update(Request $request, $id)
    {
        $pengumuman = Pengumuman::findOrFail($id);

        $request->validate([
            'judul' => 'required',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'gambar' => 'nullable|image|max:10240',
        ]);

        $gambarPath = $pengumuman->gambar;
        if ($request->hasFile('gambar')) {
            // Hapus gambar lama jika ada
            if ($pengumuman->gambar) {
                Storage::disk('public')->delete($pengumuman->gambar);
            }
            // Upload gambar baru
            $gambarPath = $request->file('gambar')->store('uploads/pengumuman', 'public');
        }

        $pengumuman->update([
            'judul' => $request->judul,
            'isi' => $request->isi,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'gambar' => $gambarPath,
        ]);

        return Redirect::back()->with(['success' => 'Pengumuman Berhasil Diperbarui']);
    }

    public function destroy($id)
    {
        $data = Pengumuman::findOrFail($id);
        if ($data->gambar) {
            Storage::disk('public')->delete($data->gambar);
        }
        $data->delete();

        return Redirect::back()->with(['success' => 'Data Berhasil Dihapus']);
    }

    public function toggleStatus($id)
    {
        $pengumuman = Pengumuman::findOrFail($id);

        // Mengubah status: Jika 1 jadi 0, jika 0 jadi 1
        $pengumuman->is_active = ! $pengumuman->is_active;

        $pengumuman->save();

        return Redirect::back()->with(['success' => 'Status Berhasil Diubah']);
    }
}
