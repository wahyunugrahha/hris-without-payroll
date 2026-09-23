<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\BpjsRequest;
use App\Models\Karyawan;
use App\Services\FotoKaryawanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class KaryawanController extends Controller
{
    public function __construct(private FotoKaryawanService $foto) {}

    // --- Profile & Izin ---
    public function profile()
    {
        $nik = auth('karyawan')->user()->nik;
        $karyawan = Karyawan::findOrFail($nik);

        return view('karyawan.profile.index', compact('karyawan'));
    }

    public function editprofile()
    {
        $nik = auth('karyawan')->user()->nik;
        $karyawan = Karyawan::findOrFail($nik);

        return view('karyawan.profile.edit_profile', compact('karyawan'));
    }

    public function updateprofile(Request $request)
    {
        $request->validate([
            'no_hp' => 'required|string|max:15',
            'email' => 'required|email|max:255',
            'nama_panggilan' => 'required|string|max:255',
            'alamat' => 'required|string',
            'agama' => 'required|string|max:255',
            'status_pernikahan' => 'required|string|max:255',
            'pendidikan_terakhir' => 'required|string|max:255',
            'no_rekening' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:3072',
        ]);

        $nik = auth('karyawan')->user()->nik;
        $karyawan = Karyawan::findOrFail($nik);

        // Cek status diderhentikan
        if ($karyawan->status_aktif == Karyawan::STATUS_DIBERHENTIKAN) {
            return redirect()->back()->with('error', 'Data tidak dapat diubah karena status Anda sudah diberhentikan.');
        }

        $data = [
            'no_hp' => $request->no_hp,
            'email' => $request->email,
            'nama_panggilan' => $request->nama_panggilan,
            'alamat' => $request->alamat,
            'agama' => $request->agama,
            'status_pernikahan' => $request->status_pernikahan,
            'pendidikan_terakhir' => $request->pendidikan_terakhir,
            'no_rekening' => $request->no_rekening,
        ];

        if (! empty($request->password)) {
            $data['password'] = Hash::make($request->password);
        }
        if ($request->hasFile('foto')) {
            $data['foto'] = $this->foto->ganti('foto', $request->file('foto'), $karyawan->foto, $nik);
        }
        $karyawan->update($data);

        return redirect()->back()->with('success', 'Profil berhasil diperbarui!');
    }

    public function profileDarurat()
    {
        $nik = auth('karyawan')->user()->nik;
        $karyawan = Karyawan::findOrFail($nik);

        return view('karyawan.profile.profile_darurat', compact('karyawan'));
    }

    public function updateDarurat(Request $request)
    {
        $request->validate([
            'nama_darurat' => 'required|string|max:255',
            'no_darurat' => 'required|string|max:255',
            'hubungan_darurat' => 'required|string|max:255',
        ]);

        $nik = auth('karyawan')->user()->nik;
        $karyawan = Karyawan::findOrFail($nik);

        // Cek status diderhentikan
        if ($karyawan->status_aktif == Karyawan::STATUS_DIBERHENTIKAN) {
            return redirect()->back()->with('error', 'Data tidak dapat diubah karena status Anda sudah diberhentikan.');
        }

        $karyawan->update([
            'nama_darurat' => $request->nama_darurat,
            'no_darurat' => $request->no_darurat,
            'hubungan_darurat' => $request->hubungan_darurat,
        ]);

        return redirect()->back()->with('success', 'Kontak Darurat berhasil diperbarui!');
    }

    public function profileAdministrasi()
    {
        $nik = auth('karyawan')->user()->nik;
        $karyawan = Karyawan::findOrFail($nik);

        $pengajuanBpjsSudahDikirim = BpjsRequest::where('nik', $nik)
            ->exists();

        $pengajuanBpjsTerakhir = BpjsRequest::where('nik', $nik)
            ->latest()
            ->first();

        return view('karyawan.profile.profile_administrasi', compact('karyawan', 'pengajuanBpjsSudahDikirim', 'pengajuanBpjsTerakhir'));
    }

    public function ajukanBpjs()
    {
        $nik = auth('karyawan')->user()->nik;
        $karyawan = Karyawan::findOrFail($nik);

        if ($karyawan->status_aktif == Karyawan::STATUS_DIBERHENTIKAN) {
            return redirect()->back()->with('error', 'Pengajuan tidak dapat dilakukan karena status Anda sudah diberhentikan.');
        }

        $sudahPernahAjukan = BpjsRequest::where('nik', $nik)
            ->exists();

        if ($sudahPernahAjukan) {
            return redirect()->back()->with('error', 'Pengajuan sudah dikirim, hanya bisa mengajukan 1x dan tidak bisa terus menerus.');
        }

        BpjsRequest::create([
            'nik' => $nik,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Pengajuan sudah dikirim, hanya bisa mengajukan 1x dan tidak bisa terus menerus.');
    }

    public function updateAdministrasi(Request $request)
    {
        $request->validate([
            'no_bpjs_kesehatan' => 'nullable|string|max:255',
            'foto_bpjs_kesehatan' => 'nullable|image|mimes:jpg,jpeg,png|max:3072',
        ]);

        $nik = auth('karyawan')->user()->nik;
        $karyawan = Karyawan::findOrFail($nik);

        // Cek status diderhentikan
        if ($karyawan->status_aktif == Karyawan::STATUS_DIBERHENTIKAN) {
            return redirect()->back()->with('error', 'Data tidak dapat diubah karena status Anda sudah diberhentikan.');
        }

        $data = [
            'no_bpjs_kesehatan' => $request->no_bpjs_kesehatan,
        ];

        if ($request->hasFile('foto_bpjs_kesehatan')) {
            $data['foto_bpjs_kesehatan'] = $this->foto->ganti('foto_bpjs_kesehatan', $request->file('foto_bpjs_kesehatan'), $karyawan->foto_bpjs_kesehatan, $nik);
        }

        $karyawan->update($data);

        return redirect()->back()->with('success', 'Data BPJS Kesehatan berhasil diperbarui!');
    }
}
