<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\Departemen;
use App\Models\JamKerja;
use App\Models\Karyawan;
use App\Models\KonfigurasiJkDept;
use App\Models\KonfigurasiJkDeptDetail;
use App\Models\Setjamkerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class KonfigurasiController extends Controller
{
    // Jam Kerja
    public function jamkerja(Request $request)
    {
        $query = JamKerja::query();

        if ($request->has('nama_jam_kerja') && ! empty($request->nama_jam_kerja)) {
            $query->where('nama_jam_kerja', 'ilike', '%'.$request->nama_jam_kerja.'%');
        }

        if ($request->has('lintashari') && $request->lintashari != '') {
            $query->where('lintashari', $request->lintashari);
        }

        $jam_kerja = $query->orderBy('nama_jam_kerja')
            ->paginate(25)
            ->appends($request->all());

        return view('admin.konfigurasi.jamkerja', compact('jam_kerja'));
    }

    public function storejamkerja(Request $request)
    {
        $request->validate([
            'kode_jam_kerja' => 'required|string|size:4|unique:jam_kerja,kode_jam_kerja',
            'nama_jam_kerja' => 'required|string|max:15',
            'awal_jam_masuk' => 'required|date_format:H:i',
            'jam_masuk' => 'required|date_format:H:i',
            'akhir_jam_masuk' => 'required|date_format:H:i',
            'jam_pulang' => 'required|date_format:H:i',
            'lintashari' => 'required|in:1,0',
        ]);

        $data = $request->only([
            'kode_jam_kerja',
            'nama_jam_kerja',
            'awal_jam_masuk',
            'jam_masuk',
            'akhir_jam_masuk',
            'jam_pulang',
            'lintashari',
        ]);

        try {
            JamKerja::create($data);

            return Redirect::back()->with(['success' => 'Data Jam Kerja Berhasil Disimpan']);
        } catch (\Exception $e) {
            // Log::error('Gagal menyimpan jam kerja: ' . $e->getMessage());
            return Redirect::back()->with(['warning' => 'Data Jam Kerja Gagal Disimpan. Terjadi Kesalahan Database.']);
        }
    }

    public function editjamkerja($kode_jam_kerja)
    {
        $jam_kerja = JamKerja::where('kode_jam_kerja', $kode_jam_kerja)->firstOrFail();

        return view('admin.konfigurasi.editjamkerja', compact('jam_kerja'));
    }

    public function updatejamkerja(Request $request, $kode_jam_kerja)
    {
        $request->validate([
            'kode_jam_kerja_edit' => 'required|string|size:4|unique:jam_kerja,kode_jam_kerja,'.$kode_jam_kerja.',kode_jam_kerja',
            'nama_jam_kerja' => 'required|string|max:15',
            'awal_jam_masuk' => 'required|date_format:H:i',
            'jam_masuk' => 'required|date_format:H:i',
            'akhir_jam_masuk' => 'required|date_format:H:i',
            'jam_pulang' => 'required|date_format:H:i',
            'lintashari' => 'required|in:1,0',
        ], [
            'kode_jam_kerja_edit.unique' => 'Kode Jam Kerja sudah digunakan.',
            'kode_jam_kerja_edit.size' => 'Kode Jam Kerja harus tepat 4 karakter.',
        ]);

        $new_kode_jam_kerja = strtoupper($request->kode_jam_kerja_edit);
        $data = $request->only([
            'nama_jam_kerja',
            'awal_jam_masuk',
            'jam_masuk',
            'akhir_jam_masuk',
            'jam_pulang',
            'lintashari',
        ]);
        $data['kode_jam_kerja'] = $new_kode_jam_kerja;

        try {
            // Update menggunakan DB query builder untuk menangani perubahan Primary Key
            $update = DB::table('jam_kerja')->where('kode_jam_kerja', $kode_jam_kerja)->update($data);

            if ($update) {
                return Redirect::route('konfigurasi.jamkerja')->with(['success' => 'Data Jam Kerja Berhasil Diupdate']);
            } else {
                return Redirect::back()->with(['warning' => 'Tidak Ada Perubahan Data']);
            }
        } catch (\Exception $e) {
            return Redirect::back()->with(['warning' => $this->failMessage('Data Jam Kerja Gagal Diupdate.', $e)]);
        }
    }

    public function checkRelationsJamKerja($kode_jam_kerja)
    {
        try {
            $jamkerja = JamKerja::where('kode_jam_kerja', $kode_jam_kerja)->firstOrFail();

            return response()->json([
                'success' => true,
                'relations' => [
                    'presensi' => $jamkerja->presensis()->count(),
                    'configuration' => $jamkerja->deptDetails()->count(),
                    'personal' => $jamkerja->personalSchedules()->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $this->failMessage('Gagal memproses data.', $e),
            ], 500);
        }
    }

    public function destroyjamkerja($kode_jam_kerja)
    {
        try {
            $jamkerja = JamKerja::where('kode_jam_kerja', $kode_jam_kerja)->first();
            if (! $jamkerja) {
                return Redirect::back()->with(['warning' => 'Data Jam Kerja tidak ditemukan']);
            }

            $jamkerja->delete();

            return Redirect::back()->with(['success' => 'Data Jam Kerja Berhasil Dihapus']);
        } catch (\Exception $e) {
            return Redirect::back()->with(['warning' => $this->failMessage('Data Jam Kerja Gagal Dihapus.', $e)]);
        }
    }

    // Jam Kerja Departemen
    public function jamkerjadept(Request $request)
    {
        $user = Auth::guard('user')->user();
        $isAdminCabang = (bool) $user?->isAdminCabang();
        $forcedKodeCabang = $isAdminCabang ? ($user->kode_cabang ?? null) : null;

        $jamkerjadept = DB::table('konfigurasi_jk_dept')
            ->join('cabang', 'konfigurasi_jk_dept.kode_cabang', '=', 'cabang.kode_cabang')
            ->join('departemen', 'konfigurasi_jk_dept.kode_dept', '=', 'departemen.kode_dept')
            ->when($forcedKodeCabang, function ($q) use ($forcedKodeCabang) {
                $q->where('konfigurasi_jk_dept.kode_cabang', $forcedKodeCabang);
            })
            ->when($request->kode_cabang, function ($q) use ($request) {
                $q->where('konfigurasi_jk_dept.kode_cabang', $request->kode_cabang);
            })
            ->when($request->kode_dept, function ($q) use ($request) {
                $q->where('konfigurasi_jk_dept.kode_dept', $request->kode_dept);
            })
            ->get();

        $cabang = Cabang::orderBy('nama_cabang', 'asc')->get();
        $departemen = Departemen::orderBy('nama_dept', 'asc')->get();

        return view('admin.konfigurasi.jamkerjadept', compact('jamkerjadept', 'cabang', 'departemen', 'forcedKodeCabang'));
    }

    public function createjamkerjadept()
    {
        $jamkerja = JamKerja::orderBy('nama_jam_kerja')->get();
        $user = Auth::guard('user')->user();
        $isAdminCabang = (bool) $user?->isAdminCabang();
        $cabang = $isAdminCabang && ! empty($user->kode_cabang)
            ? Cabang::where('kode_cabang', $user->kode_cabang)->get()
            : Cabang::get();
        $departemen = Departemen::get();

        return view('admin.konfigurasi.createjamkerjadept', compact('jamkerja', 'cabang', 'departemen'));
    }

    public function storejamkerjadept(Request $request)
    {
        $kode_cabang = $request->kode_cabang;
        $kode_dept = $request->kode_dept;
        $hari = $request->hari;
        $kode_jam_kerja = $request->kode_jam_kerja;
        $kode_jk_dept = 'J'.$kode_cabang.$kode_dept;

        $user = Auth::guard('user')->user();
        $isAdminCabang = (bool) $user?->isAdminCabang();
        if ($isAdminCabang && ! empty($user->kode_cabang) && $user->kode_cabang !== $kode_cabang) {
            return redirect('/konfigurasi/jamkerjadept')->with(['warning' => 'Anda hanya dapat mengatur jam kerja departemen untuk cabang Anda.']);
        }

        DB::beginTransaction();
        try {
            // Menyimpan Data ke Table Konfigurasi_jk_dept
            DB::table('konfigurasi_jk_dept')->insert([
                'kode_jk_dept' => $kode_jk_dept,
                'kode_cabang' => $kode_cabang,
                'kode_dept' => $kode_dept,
            ]);

            for ($i = 0; $i < count($hari); $i++) {
                $data[] = [
                    'kode_jk_dept' => $kode_jk_dept,
                    'hari' => $hari[$i],
                    'kode_jam_kerja' => ($kode_jam_kerja[$i] === '' || $kode_jam_kerja[$i] === 'LIBUR') ? null : $kode_jam_kerja[$i],
                ];
            }

            KonfigurasiJkDeptDetail::insert($data);
            DB::commit();

            return redirect('/konfigurasi/jamkerjadept')->with(['success' => 'Data Berhasil Disimpan']);
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect('/konfigurasi/jamkerjadept')->with(['warning' => 'Data Gagal Disimpan']);
        }
    }

    public function editjamkerjadept($kode_jk_dept)
    {
        $jamkerja = JamKerja::orderBy('nama_jam_kerja')->get();
        $user = Auth::guard('user')->user();
        $isAdminCabang = (bool) $user?->isAdminCabang();
        $cabang = $isAdminCabang && ! empty($user->kode_cabang)
            ? Cabang::where('kode_cabang', $user->kode_cabang)->get()
            : Cabang::get();
        $departemen = Departemen::get();
        $jamkerjadept = DB::table('konfigurasi_jk_dept')
            ->where('kode_jk_dept', $kode_jk_dept)
            ->first();

        if (! $jamkerjadept) {
            return redirect('/konfigurasi/jamkerjadept')->with(['warning' => 'Data tidak ditemukan']);
        }

        if ($isAdminCabang && ! empty($user->kode_cabang) && $jamkerjadept->kode_cabang !== $user->kode_cabang) {
            return redirect('/konfigurasi/jamkerjadept')->with(['warning' => 'Anda tidak berhak mengubah jam kerja cabang lain.']);
        }
        $jamkerjadept_detail = DB::table('konfigurasi_jk_dept_detail')
            ->where('kode_jk_dept', $kode_jk_dept)
            ->get();

        return view('admin.konfigurasi.editjamkerjadept', compact('jamkerja', 'cabang', 'departemen', 'jamkerjadept', 'jamkerjadept_detail'));
    }

    public function updatejamkerjadept($kode_jk_dept, Request $request)
    {
        $user = Auth::guard('user')->user();
        $isAdminCabang = (bool) $user?->isAdminCabang();
        $header = DB::table('konfigurasi_jk_dept')->where('kode_jk_dept', $kode_jk_dept)->first();
        if (! $header) {
            return redirect('/konfigurasi/jamkerjadept')->with(['warning' => 'Data tidak ditemukan']);
        }
        if ($isAdminCabang && ! empty($user->kode_cabang) && $header->kode_cabang !== $user->kode_cabang) {
            return redirect('/konfigurasi/jamkerjadept')->with(['warning' => 'Anda tidak berhak mengubah jam kerja cabang lain.']);
        }
        $hari = $request->hari;
        $kode_jam_kerja = $request->kode_jam_kerja;

        DB::beginTransaction();

        try {
            DB::table('konfigurasi_jk_dept_detail')->where('kode_jk_dept', $kode_jk_dept)->delete();

            $data = [];
            for ($i = 0; $i < count($hari); $i++) {
                $data[] = [
                    'kode_jk_dept' => $kode_jk_dept,
                    'hari' => $hari[$i],
                    'kode_jam_kerja' => ($kode_jam_kerja[$i] === '' || $kode_jam_kerja[$i] === 'LIBUR') ? null : $kode_jam_kerja[$i],
                ];
            }

            KonfigurasiJkDeptDetail::insert($data);
            DB::commit();

            return redirect('/konfigurasi/jamkerjadept')->with(['success' => 'Data Berhasil Disimpan']);
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect('/konfigurasi/jamkerjadept')->with(['warning' => 'Data Gagal Disimpan']);
        }
    }

    public function showjamkerjadept($kode_jk_dept)
    {
        $jamkerja = JamKerja::orderBy('nama_jam_kerja')->get();
        $user = Auth::guard('user')->user();
        $isAdminCabang = (bool) $user?->isAdminCabang();
        $cabang = DB::table('cabang')
            ->when($isAdminCabang && ! empty($user->kode_cabang), function ($q) use ($user) {
                $q->where('kode_cabang', $user->kode_cabang);
            })->get();
        $departemen = DB::table('departemen')->get();
        $jamkerjadept = DB::table('konfigurasi_jk_dept')->where('kode_jk_dept', $kode_jk_dept)->first();
        if (! $jamkerjadept) {
            return redirect('/konfigurasi/jamkerjadept')->with(['warning' => 'Data tidak ditemukan']);
        }
        if ($isAdminCabang && ! empty($user->kode_cabang) && $jamkerjadept->kode_cabang !== $user->kode_cabang) {
            return redirect('/konfigurasi/jamkerjadept')->with(['warning' => 'Anda tidak berhak melihat jam kerja cabang lain.']);
        }
        $jamkerjadept_detail = DB::table('konfigurasi_jk_dept_detail')
            ->leftJoin('jam_kerja', 'konfigurasi_jk_dept_detail.kode_jam_kerja', '=', 'jam_kerja.kode_jam_kerja')
            ->select('konfigurasi_jk_dept_detail.*', 'jam_kerja.nama_jam_kerja', 'jam_kerja.jam_masuk', 'jam_kerja.jam_pulang')
            ->where('kode_jk_dept', $kode_jk_dept)->get();

        return view('admin.konfigurasi.showjamkerjadept', compact('jamkerja', 'cabang', 'departemen', 'jamkerjadept', 'jamkerjadept_detail'));
    }

    public function checkRelationsJamKerjaDept($kode_jk_dept)
    {
        try {
            $header = KonfigurasiJkDept::where('kode_jk_dept', $kode_jk_dept)->firstOrFail();
            $karyawanCount = Karyawan::where('kode_cabang', $header->kode_cabang)
                ->where('kode_dept', $header->kode_dept)
                ->count();

            return response()->json([
                'success' => true,
                'relations' => [
                    'karyawan' => $karyawanCount,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $this->failMessage('Gagal memproses data.', $e),
            ], 500);
        }
    }

    public function deletejamkerjadept($kode_jk_dept)
    {
        try {
            $user = Auth::guard('user')->user();
            $isAdminCabang = (bool) $user?->isAdminCabang();
            $header = KonfigurasiJkDept::where('kode_jk_dept', $kode_jk_dept)->first();

            if (! $header) {
                return Redirect::back()->with(['warning' => 'Data konfigurasi tidak ditemukan']);
            }

            if ($isAdminCabang && ! empty($user->kode_cabang) && $header->kode_cabang !== $user->kode_cabang) {
                return Redirect::back()->with(['warning' => 'Anda tidak berhak menghapus jam kerja cabang lain.']);
            }

            $header->delete();

            return Redirect::back()->with(['success' => 'Data Konfigurasi Jam Kerja Berhasil Dihapus']);
        } catch (\Exception $e) {
            return Redirect::back()->with(['warning' => $this->failMessage('Data Gagal Dihapus.', $e)]);
        }
    }

    // Set Jam Kerja Untuk Karyawan
    public function setjamkerja($nik)
    {
        $karyawan = Karyawan::where('nik', $nik)->firstOrFail();
        $user = Auth::guard('user')->user();
        $isAdminCabang = (bool) $user?->isAdminCabang();
        if ($isAdminCabang && $user->kode_cabang !== $karyawan->kode_cabang) {
            return Redirect::back()->with(['warning' => 'Anda hanya bisa mengatur jam kerja karyawan di cabang Anda.']);
        }
        $jamkerja = JamKerja::orderBy('nama_jam_kerja')->get();
        $cekjamkerja = Setjamkerja::where('nik', $nik)->count();

        if ($cekjamkerja > 0) {
            // Menggunakan Eloquent dengan Eager Loading
            $setjamkerja = Setjamkerja::where('nik', $nik)
                ->with('jamKerja')
                ->get();

            return view('admin.konfigurasi.setjamkerja', compact('karyawan', 'jamkerja', 'setjamkerja'));
        } else {
            return view('admin.konfigurasi.setjamkerja', compact('karyawan', 'jamkerja'));
        }
    }

    public function setstorejamkerja(Request $request)
    {
        $request->validate([
            'nik' => 'required|string|exists:karyawan,nik',
            'hari' => 'required|array',
            'kode_jam_kerja' => 'required|array',
        ]);

        $nik = $request->nik;
        $hari = $request->hari;
        $kode_jam_kerja = $request->kode_jam_kerja;

        $karyawan = Karyawan::where('nik', $nik)->first();
        $user = Auth::guard('user')->user();
        $isAdminCabang = (bool) $user?->isAdminCabang();

        if ($isAdminCabang && $karyawan && $user->kode_cabang !== $karyawan->kode_cabang) {
            return Redirect::back()->with(['warning' => 'Anda hanya bisa mengatur jam kerja karyawan di cabang Anda.']);
        }

        // Validasi Sederhana
        if (! is_array($hari) || count($hari) != 7) {
            return Redirect::back()->with(['warning' => 'Data hari tidak lengkap.']);
        }

        $data = [];
        for ($i = 0; $i < count($hari); $i++) {
            $kode = $kode_jam_kerja[$i];

            // LOGIKA DIPERBAIKI: Tangkap 'null' (bawaan konversi Laravel) ATAU string kosong
            if ($kode === null || $kode === '') {
                continue; // Skip baris ini agar sistem otomatis ikut jadwal departemen
            }

            $data[] = [
                'nik' => $nik,
                'hari' => $hari[$i],
                // Jika LIBUR, set null. Jika ada kode jam kerja, masukkan kodenya.
                'kode_jam_kerja' => ($kode === 'LIBUR') ? null : $kode,
            ];
        }

        try {
            DB::transaction(function () use ($nik, $data) {
                Setjamkerja::where('nik', $nik)->delete();

                // Hanya insert jika ada override jam kerja personal
                if (! empty($data)) {
                    Setjamkerja::insert($data);
                }
            });

            return redirect('/karyawan')->with(['success' => 'Jam Kerja Berhasil Di Seting']);
        } catch (\Exception $e) {
            return redirect('/karyawan')->with(['warning' => $this->failMessage('Jam Kerja Gagal Di Seting.', $e)]);
        }
    }

    public function updatesetjamkerja(Request $request)
    {
        $request->validate([
            'nik' => 'required|string|exists:karyawan,nik',
            'hari' => 'required|array',
            'kode_jam_kerja' => 'required|array',
        ]);

        $nik = $request->nik;
        $hari = $request->hari;
        $kode_jam_kerja = $request->kode_jam_kerja;

        $karyawan = Karyawan::where('nik', $nik)->first();
        $user = Auth::guard('user')->user();
        $isAdminCabang = (bool) $user?->isAdminCabang();

        if ($isAdminCabang && $karyawan && $user->kode_cabang !== $karyawan->kode_cabang) {
            return redirect('/karyawan')->with(['warning' => 'Anda hanya bisa mengatur jam kerja karyawan di cabang Anda.']);
        }

        if (count($hari) !== count($kode_jam_kerja)) {
            return redirect('/karyawan')->with(['warning' => 'Jumlah data hari dan jam kerja tidak sesuai.']);
        }

        $data = [];
        for ($i = 0; $i < count($hari); $i++) {
            $kode = $kode_jam_kerja[$i];

            // LOGIKA DIPERBAIKI: Tangkap 'null' (bawaan konversi Laravel) ATAU string kosong
            if ($kode === null || $kode === '') {
                continue; // Skip baris ini agar sistem otomatis ikut jadwal departemen
            }

            $data[] = [
                'nik' => $nik,
                'hari' => $hari[$i],
                'kode_jam_kerja' => ($kode === 'LIBUR') ? null : $kode,
            ];
        }

        try {
            DB::transaction(function () use ($nik, $data) {
                Setjamkerja::where('nik', $nik)->delete();

                if (! empty($data)) {
                    Setjamkerja::insert($data);
                }
            });

            return redirect('/karyawan')->with(['success' => 'Jam Kerja Berhasil Di Seting']);
        } catch (\Exception $e) {
            return redirect('/karyawan')->with(['warning' => 'Jam Kerja Gagal Di Seting: Terjadi kesalahan database.']);
        }
    }

    public function getjamkerja()
    {
        $jamkerja = JamKerja::orderBy('nama_jam_kerja', 'asc')->get();

        return response()->json($jamkerja);
    }

    public function setallbycabang(Request $request)
    {
        try {
            $request->validate([
                'kode_cabang_set' => 'required',
                'jam_kerja' => 'required|array',
                'jam_kerja.*' => 'required',
            ]);

            $user = Auth::guard('user')->user();
            $isAdminCabang = (bool) $user?->isAdminCabang();
            $kodeCabang = $request->kode_cabang_set;

            // Validasi admin cabang hanya bisa set untuk cabang mereka
            if ($isAdminCabang && ! empty($user->kode_cabang) && $kodeCabang !== $user->kode_cabang) {
                return response()->json(['message' => 'Anda tidak berhak mengatur jam kerja cabang lain.'], 403);
            }

            // Ambil semua departemen
            $departemen = Departemen::all();
            $jamKerjaPerHari = $request->jam_kerja; // Array dengan key hari
            $updated = 0;

            foreach ($departemen as $dept) {
                // Cek apakah sudah ada konfigurasi untuk cabang-dept ini
                $existing = KonfigurasiJkDept::where('kode_cabang', $kodeCabang)
                    ->where('kode_dept', $dept->kode_dept)
                    ->first();

                if (! $existing) {
                    // Buat header baru jika belum ada
                    $kodeJkDept = 'JKD'.$kodeCabang.$dept->kode_dept;
                    $existing = KonfigurasiJkDept::create([
                        'kode_jk_dept' => $kodeJkDept,
                        'kode_cabang' => $kodeCabang,
                        'kode_dept' => $dept->kode_dept,
                    ]);
                }

                // Loop untuk setiap hari yang dipilih
                foreach ($jamKerjaPerHari as $hari => $kodeJamKerja) {
                    if (empty($kodeJamKerja)) {
                        continue;
                    }
                    $kodeJamKerjaVal = ($kodeJamKerja === 'LIBUR') ? null : $kodeJamKerja;

                    // Cek apakah sudah ada detail untuk hari ini
                    $detail = KonfigurasiJkDeptDetail::where('kode_jk_dept', $existing->kode_jk_dept)
                        ->where('hari', $hari)
                        ->first();

                    if ($detail) {
                        // Update jika sudah ada
                        $detail->update(['kode_jam_kerja' => $kodeJamKerjaVal]);
                    } else {
                        // Insert jika belum ada
                        KonfigurasiJkDeptDetail::create([
                            'kode_jk_dept' => $existing->kode_jk_dept,
                            'hari' => $hari,
                            'kode_jam_kerja' => $kodeJamKerjaVal,
                        ]);
                    }
                }
                $updated++;
            }

            return response()->json([
                'message' => "Berhasil mengatur jam kerja untuk {$updated} departemen untuk semua hari yang dipilih",
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $this->failMessage('Gagal memproses data.', $e)], 500);
        }
    }
}
