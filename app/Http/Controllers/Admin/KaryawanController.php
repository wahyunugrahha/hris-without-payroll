<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\BusinessException;
use App\Exports\KaryawanExport;
use App\Exports\KaryawanTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\KaryawanImport;
use App\Models\Cabang;
use App\Models\Departemen;
use App\Models\Jabatan;
use App\Models\Karyawan;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class KaryawanController extends Controller
{
    // Regex Patterns
    private $nama_regex = 'regex:/^[\pL\s\.\,\-]+$/u';

    private $angka_regex = 'regex:/^[0-9]+$/';

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

    private function getStatusFilterOptions()
    {
        return Karyawan::FILTERABLE_STATUSES;
    }

    private function normalizeStatusFilter(?string $statusFilter, ?string $default = null)
    {
        return in_array($statusFilter, Karyawan::FILTERABLE_STATUSES, true) ? $statusFilter : $default;
    }

    public function index(Request $request)
    {
        $forcedKodeCabang = $this->getForcedCabang();
        $statusFilter = $this->normalizeStatusFilter($request->get('status_filter'), Karyawan::STATUS_AKTIF);

        // Menggunakan Eloquent pure agar lebih bersih dan aman dari collision nama kolom
        $query = Karyawan::with(['jabatanRel', 'departemen', 'cabang'])
            ->orderByRaw("CASE WHEN status_aktif = '".Karyawan::STATUS_MENUNGGU_APPROVAL."' THEN 0 ELSE 1 END ASC")
            ->orderBy('nama_lengkap', 'asc');

        $query->filterStatus($statusFilter);

        // Filter Nama
        if ($request->filled('nama_karyawan')) {
            $query->where(function ($q) use ($request) {
                $q->where('nama_lengkap', 'ilike', '%'.$request->nama_karyawan.'%')
                    ->orWhere('nik', 'ilike', '%'.$request->nama_karyawan.'%');
            });
        }

        // Filter Departemen
        if ($request->filled('kode_dept')) {
            $query->where('kode_dept', $request->kode_dept);
        }

        // Filter Jabatan (single)
        if ($request->filled('jabatan_id')) {
            $query->where('jabatan_id', $request->jabatan_id);
        }

        // Filter Cabang (Security Logic)
        if (! empty($forcedKodeCabang)) {
            $query->where('kode_cabang', $forcedKodeCabang);
        } elseif ($request->filled('kode_cabang')) {
            $query->where('kode_cabang', $request->kode_cabang);
        }

        $karyawan = $query->paginate(50);

        // Data Pendukung View
        $departemen = Departemen::orderBy('nama_dept')->get();
        $jabatans = Jabatan::whereHas('role', function ($q) {
            $q->where('guard_name', 'karyawan');
        })->orderBy('nama_jabatan', 'asc')->get();

        $cabang = ! empty($forcedKodeCabang)
            ? Cabang::where('kode_cabang', $forcedKodeCabang)->get()
            : Cabang::orderBy('nama_cabang', 'asc')->get();

        $statusFilterOptions = $this->getStatusFilterOptions();

        return view('admin.karyawan.index', compact('karyawan', 'departemen', 'cabang', 'jabatans', 'statusFilterOptions', 'statusFilter'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nik' => ['required', 'string', 'max:20', 'unique:karyawan,nik', $this->angka_regex],
            'nama_lengkap' => ['required', 'string', 'max:255', $this->nama_regex],
            'nama_panggilan' => ['required', 'string', 'max:255'],
            'jabatan_id' => 'required|exists:jabatan,id',
            'no_hp' => ['required', 'string', 'max:20', $this->angka_regex],
            'kode_dept' => 'required',
            'kode_cabang' => 'required',
            'is_whitelist' => 'nullable|boolean',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:3072',
            'foto_bpjs_kesehatan' => 'nullable|image|mimes:jpeg,png,jpg|max:3072',
            'foto_bpjs_ketenagakerjaan' => 'nullable|image|mimes:jpeg,png,jpg|max:3072',
            'tmt' => 'nullable|date',
            'tanggal_awal_kontrak' => 'nullable|date',
            'email' => 'nullable|email',
        ]);

        DB::beginTransaction();
        try {
            $forcedCabang = $this->getForcedCabang();
            $nik = $request->nik;
            $foto = null;

            // Upload handling (persiapan nama file)
            $foto = null;
            $foto_bpjs_kes = null;
            $foto_bpjs_ket = null;

            if ($request->hasFile('foto')) {
                $foto = $nik.'_'.time().'.'.$request->file('foto')->extension();
            }
            if ($request->hasFile('foto_bpjs_kesehatan')) {
                $foto_bpjs_kes = $nik.'_bpjs_kes_'.time().'.'.$request->file('foto_bpjs_kesehatan')->extension();
            }
            if ($request->hasFile('foto_bpjs_ketenagakerjaan')) {
                $foto_bpjs_ket = $nik.'_bpjs_ket_'.time().'.'.$request->file('foto_bpjs_ketenagakerjaan')->extension();
            }

            // Persiapan Data
            $data = $request->except(['foto', 'password', 'foto_bpjs_kesehatan', 'foto_bpjs_ketenagakerjaan']);
            $jabatan = Jabatan::with('role')->find($request->jabatan_id);

            // SECURITY: Paksa kode cabang jika user adalah admin cabang (mencegah inspect element)
            if (! empty($forcedCabang)) {
                $data['kode_cabang'] = $forcedCabang;
            }

            $data['foto'] = $foto;
            $data['foto_bpjs_kesehatan'] = $foto_bpjs_kes;
            $data['foto_bpjs_ketenagakerjaan'] = $foto_bpjs_ket;
            $data['password'] = Hash::make('123456'); // Default password, wajib diganti saat login pertama
            $data['must_change_password'] = true;
            $data['status_aktif'] = 'Aktif';
            $data['is_whitelist'] = $request->boolean('is_whitelist') ? 1 : 0;

            // 1. Simpan DB
            $karyawan = Karyawan::create($data);

            if ($jabatan && $jabatan->role) {
                $karyawan->syncRoles([$jabatan->role->name]);
            }

            // 2. Simpan File
            if ($request->hasFile('foto')) {
                $request->file('foto')->storeAs('uploads/karyawan/', $foto, 'public');
            }
            if ($request->hasFile('foto_bpjs_kesehatan')) {
                $request->file('foto_bpjs_kesehatan')->storeAs('uploads/karyawan/bpjs/', $foto_bpjs_kes, 'public');
            }
            if ($request->hasFile('foto_bpjs_ketenagakerjaan')) {
                $request->file('foto_bpjs_ketenagakerjaan')->storeAs('uploads/karyawan/bpjs/', $foto_bpjs_ket, 'public');
            }

            DB::commit();

            return Redirect::back()->with(['success' => 'Data Karyawan Berhasil Disimpan']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error Store Karyawan: '.$e->getMessage());

            // Added withInput() agar user tidak perlu mengetik ulang
            return Redirect::back()->with(['warning' => $this->failMessage('Gagal disimpan.', $e)])->withInput();
        }
    }

    public function edit($nik)
    {
        try {
            $karyawan = Karyawan::findOrFail($nik);
            $forcedCabang = $this->getForcedCabang();

            // Security Check: Status Diberhentikan tidak bisa diedit
            if ($karyawan->status_aktif === Karyawan::STATUS_DIBERHENTIKAN) {
                return Redirect::back()->with(['warning' => 'Karyawan yang sudah Diberhentikan tidak dapat diedit.']);
            }

            // Security Check: Akses cabang
            if (! empty($forcedCabang) && $karyawan->kode_cabang !== $forcedCabang) {
                return Redirect::route('karyawan.index')->with(['warning' => 'Akses Ditolak: Karyawan berbeda cabang.']);
            }

            $departemen = Departemen::orderBy('nama_dept')->get();
            $jabatans = Jabatan::whereHas('role', function ($q) {
                $q->where('guard_name', 'karyawan');
            })->orderBy('nama_jabatan')->get();

            $cabang = ! empty($forcedCabang)
                ? Cabang::where('kode_cabang', $forcedCabang)->get()
                : Cabang::orderBy('nama_cabang')->get();

            return view('admin.karyawan.edit', compact('karyawan', 'departemen', 'cabang', 'jabatans'));

        } catch (\Exception $e) {
            return Redirect::back()->with(['warning' => 'Data tidak ditemukan.']);
        }
    }

    public function update(Request $request, $nik)
    {
        $tanggalKeluarRequiredStatuses = [Karyawan::STATUS_NONAKTIF, Karyawan::STATUS_DIBERHENTIKAN];

        // Asumsi Primary Key adalah 'nik' (string). Jika PK 'id', sesuaikan parameter unique ke-3
        $request->validate([
            'nik' => ['required', 'string', 'max:20', 'unique:karyawan,nik,'.$nik.',nik', $this->angka_regex],
            'nama_lengkap' => ['required', 'string', 'max:255', $this->nama_regex],
            'nama_panggilan' => ['required', 'string', 'max:255'],
            'jabatan_id' => 'required|exists:jabatan,id',
            'no_hp' => ['required', $this->angka_regex],
            'kode_dept' => 'required',
            'kode_cabang' => 'required',
            'is_whitelist' => 'nullable|boolean',
            'email' => 'nullable|email',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:3072',
            'foto_bpjs_kesehatan' => 'nullable|image|mimes:jpeg,png,jpg|max:3072',
            'foto_bpjs_ketenagakerjaan' => 'nullable|image|mimes:jpeg,png,jpg|max:3072',
            'tmt' => 'nullable|date',
            'tanggal_awal_kontrak' => 'nullable|date',
            'status_aktif' => ['nullable', Rule::in(Karyawan::FILTERABLE_STATUSES)],
            'tanggal_habis_kontrak' => 'nullable|date',
            'tanggal_keluar' => [
                'nullable',
                'date',
                Rule::requiredIf(fn () => in_array($request->status_aktif, $tanggalKeluarRequiredStatuses, true)),
            ],
        ], [
            'nik.unique' => 'NIK Baru sudah terdaftar pada karyawan lain.',
            'tanggal_keluar.required' => 'Tanggal keluar wajib diisi jika status karyawan Nonaktif atau Diberhentikan.',
        ]);

        $karyawan = Karyawan::findOrFail($nik);
        if ($karyawan->status_aktif === Karyawan::STATUS_DIBERHENTIKAN) {
            return Redirect::back()->with(['warning' => 'Karyawan yang sudah Diberhentikan tidak dapat diedit.']);
        }

        DB::beginTransaction();
        try {
            $karyawan = Karyawan::where('nik', $nik)->firstOrFail();
            $forcedCabang = $this->getForcedCabang();

            // Security Check
            if (! empty($forcedCabang) && $karyawan->kode_cabang !== $forcedCabang) {
                throw new BusinessException('Anda tidak berhak mengedit data cabang lain.');
            }

            $oldFoto = $karyawan->foto;
            $oldBpjsKes = $karyawan->foto_bpjs_kesehatan;
            $oldBpjsKet = $karyawan->foto_bpjs_ketenagakerjaan;
            $oldNik = $karyawan->nik;
            $newNik = $request->nik;

            // Persiapan Payload
            $payload = $request->except(['_token', '_method', 'password', 'foto', 'foto_bpjs_kesehatan', 'foto_bpjs_ketenagakerjaan']);
            $jabatan = Jabatan::with('role')->find($request->jabatan_id);

            // SECURITY: Paksa kode cabang lagi saat update
            if (! empty($forcedCabang)) {
                $payload['kode_cabang'] = $forcedCabang;
            }

            if ($request->filled('password')) {
                $payload['password'] = Hash::make($request->password);
                $payload['must_change_password'] = true; // password diketahui admin, karyawan wajib menggantinya
            }

            $payload['is_whitelist'] = $request->boolean('is_whitelist') ? 1 : 0;

            // Logic Nama Foto
            $newFotoName = $oldFoto;

            if ($request->hasFile('foto')) {
                // Case 1: Upload Foto Baru -> Nama = NIK Baru + Ext Baru
                $extension = $request->file('foto')->extension();
                $newFotoName = $newNik.'.'.$extension;
            } elseif ($oldNik != $newNik && ! empty($oldFoto)) {
                // Case 2: Ganti NIK saja -> Nama = NIK Baru + Ext Lama
                $extension = pathinfo($oldFoto, PATHINFO_EXTENSION) ?: 'jpg';
                $newFotoName = $newNik.'.'.$extension;
            }

            $payload['foto'] = $newFotoName;

            // Logic Nama BPJS Kesehatan
            $newBpjsKesName = $oldBpjsKes;
            if ($request->hasFile('foto_bpjs_kesehatan')) {
                $newBpjsKesName = $newNik.'_bpjs_kes_'.time().'.'.$request->file('foto_bpjs_kesehatan')->extension();
            } elseif ($oldNik != $newNik && ! empty($oldBpjsKes)) {
                $extension = pathinfo($oldBpjsKes, PATHINFO_EXTENSION) ?: 'jpg';
                $newBpjsKesName = $newNik.'_bpjs_kes_'.time().'.'.$extension;
            }
            $payload['foto_bpjs_kesehatan'] = $newBpjsKesName;

            // Logic Nama BPJS Ketenagakerjaan
            $newBpjsKetName = $oldBpjsKet;
            if ($request->hasFile('foto_bpjs_ketenagakerjaan')) {
                $newBpjsKetName = $newNik.'_bpjs_ket_'.time().'.'.$request->file('foto_bpjs_ketenagakerjaan')->extension();
            } elseif ($oldNik != $newNik && ! empty($oldBpjsKet)) {
                $extension = pathinfo($oldBpjsKet, PATHINFO_EXTENSION) ?: 'jpg';
                $newBpjsKetName = $newNik.'_bpjs_ket_'.time().'.'.$extension;
            }
            $payload['foto_bpjs_ketenagakerjaan'] = $newBpjsKetName;

            // Auto-reaktivasi: jika karyawan Nonaktif dan tanggal_habis_kontrak diperpanjang ke masa depan
            $newKontrak = $request->tanggal_habis_kontrak;
            if (
                $karyawan->status_aktif === Karyawan::STATUS_NONAKTIF &&
                ! empty($newKontrak) &&
                Carbon::parse($newKontrak)->isFuture() &&
                (
                    empty($karyawan->tanggal_habis_kontrak) ||
                    Carbon::parse($newKontrak)->gt(Carbon::parse($karyawan->tanggal_habis_kontrak))
                )
            ) {
                $payload['status_aktif'] = Karyawan::STATUS_AKTIF;
                $payload['tanggal_keluar'] = null;
            }

            // 1. Update DB
            $oldDataKaryawan = $karyawan->toArray();
            Karyawan::where('nik', $nik)->update($payload);

            $karyawanUpdated = Karyawan::where('nik', $newNik)->first();

            if ($karyawanUpdated) {
                if ($jabatan && $jabatan->role) {
                    $karyawanUpdated->syncRoles([$jabatan->role->name]);
                }
            }

            // 2. Operasi File Profile
            if ($request->hasFile('foto')) {
                $request->file('foto')->storeAs('uploads/karyawan/', $newFotoName, 'public');
                if ($oldFoto && $oldFoto != $newFotoName && Storage::disk('public')->exists('uploads/karyawan/'.$oldFoto)) {
                    Storage::disk('public')->delete('uploads/karyawan/'.$oldFoto);
                }
            } elseif ($oldNik != $newNik && ! empty($oldFoto)) {
                if (Storage::disk('public')->exists('uploads/karyawan/'.$oldFoto)) {
                    Storage::disk('public')->move('uploads/karyawan/'.$oldFoto, 'uploads/karyawan/'.$newFotoName);
                }
            }

            // 3. Operasi File BPJS Kesehatan
            if ($request->hasFile('foto_bpjs_kesehatan')) {
                $request->file('foto_bpjs_kesehatan')->storeAs('uploads/karyawan/bpjs/', $newBpjsKesName, 'public');
                if ($oldBpjsKes && $oldBpjsKes != $newBpjsKesName && Storage::disk('public')->exists('uploads/karyawan/bpjs/'.$oldBpjsKes)) {
                    Storage::disk('public')->delete('uploads/karyawan/bpjs/'.$oldBpjsKes);
                }
            } elseif ($oldNik != $newNik && ! empty($oldBpjsKes)) {
                if (Storage::disk('public')->exists('uploads/karyawan/bpjs/'.$oldBpjsKes)) {
                    Storage::disk('public')->move('uploads/karyawan/bpjs/'.$oldBpjsKes, 'uploads/karyawan/bpjs/'.$newBpjsKesName);
                }
            }

            // 4. Operasi File BPJS Ketenagakerjaan
            if ($request->hasFile('foto_bpjs_ketenagakerjaan')) {
                $request->file('foto_bpjs_ketenagakerjaan')->storeAs('uploads/karyawan/bpjs/', $newBpjsKetName, 'public');
                if ($oldBpjsKet && $oldBpjsKet != $newBpjsKetName && Storage::disk('public')->exists('uploads/karyawan/bpjs/'.$oldBpjsKet)) {
                    Storage::disk('public')->delete('uploads/karyawan/bpjs/'.$oldBpjsKet);
                }
            } elseif ($oldNik != $newNik && ! empty($oldBpjsKet)) {
                if (Storage::disk('public')->exists('uploads/karyawan/bpjs/'.$oldBpjsKet)) {
                    Storage::disk('public')->move('uploads/karyawan/bpjs/'.$oldBpjsKet, 'uploads/karyawan/bpjs/'.$newBpjsKetName);
                }
            }

            DB::commit();

            return Redirect::back()->with(['success' => 'Data Karyawan Berhasil Diupdate']);

        } catch (QueryException $e) {
            DB::rollBack();
            if ($e->getCode() == '23503') { // Postgres Foreign Key Violation
                return Redirect::back()->with(['warning' => 'Gagal Update NIK: Data terkunci relasi data lain.'])->withInput();
            }

            return Redirect::back()->with(['warning' => $this->failMessage('Gagal memproses data.', $e)])->withInput();
        } catch (\Exception $e) {
            DB::rollBack();

            return Redirect::back()->with(['warning' => $this->failMessage('Gagal memproses data.', $e)])->withInput();
        }
    }

    public function show($nik)
    {
        try {
            $karyawan = Karyawan::with(['departemen', 'cabang'])->where('nik', $nik)->firstOrFail();

            if ($this->outsideAdminCabang($karyawan)) {
                return Redirect::route('karyawan.index')->with(['warning' => 'Anda tidak memiliki akses ke data cabang lain.']);
            }

            return view('admin.karyawan.show', compact('karyawan'));
        } catch (\Exception $e) {
            return Redirect::route('karyawan.index')->with(['warning' => 'Data karyawan tidak ditemukan.']);
        }
    }

    public function destroy($nik)
    {
        DB::beginTransaction();
        try {
            $karyawan = Karyawan::findOrFail($nik);
            $forcedCabang = $this->getForcedCabang();

            // Security Check
            if (! empty($forcedCabang) && $karyawan->kode_cabang !== $forcedCabang) {
                return Redirect::back()->with(['warning' => 'Akses Ditolak.']);
            }

            $fotoPath = $karyawan->foto;

            // 1. Delete DB
            $karyawan->delete();

            // 2. Delete File
            if ($fotoPath && Storage::disk('public')->exists('uploads/karyawan/'.$fotoPath)) {
                Storage::disk('public')->delete('uploads/karyawan/'.$fotoPath);
            }

            DB::commit();

            return Redirect::route('karyawan.index')->with(['success' => 'Data Berhasil Dihapus']);

        } catch (QueryException $e) {
            DB::rollBack();
            if ($e->getCode() == '23503') {
                return Redirect::back()->with(['warning' => 'Tidak bisa dihapus: Karyawan memiliki riwayat data (Presensi/Izin/dll).']);
            }

            return Redirect::back()->with(['warning' => 'Gagal menghapus data (Database Error).']);
        } catch (\Exception $e) {
            DB::rollBack();

            return Redirect::back()->with(['warning' => 'Terjadi kesalahan sistem.']);
        }
    }

    public function export(Request $request)
    {
        $forcedCabang = $this->getForcedCabang();

        $filters = [
            'nama_karyawan' => $request->nama_karyawan,
            'kode_dept' => $request->kode_dept,
            'kode_cabang' => $request->kode_cabang,
        ];

        // Security Override
        if (! empty($forcedCabang)) {
            $filters['kode_cabang'] = $forcedCabang;
        }

        $fileName = 'data-karyawan-'.date('Y-m-d-His').'.xlsx';

        return Excel::download(new KaryawanExport($filters), $fileName);
    }

    public function template()
    {
        return Excel::download(new KaryawanTemplateExport, 'template-import-karyawan.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:5120',
        ], [
            'file.required' => 'File Excel wajib dipilih.',
            'file.mimes' => 'File harus berformat Excel (.xlsx atau .xls).',
        ]);

        DB::beginTransaction();
        try {
            $import = new KaryawanImport;
            Excel::import($import, $request->file('file'));

            $summary = $import->getSummary(); // Pastikan method ini ada di Import Class Anda

            DB::commit();

            $message = "Import selesai. Berhasil: {$summary['success']}, Gagal/Skip: {$summary['skipped']}";

            if (! empty($summary['errors'])) {
                // Membatasi tampilan error agar tidak memenuhi session
                $errorList = implode('<br>', array_map('e', array_slice($summary['errors'], 0, 10)));
                if (count($summary['errors']) > 10) {
                    $errorList .= '<br>... dan '.(count($summary['errors']) - 10).' error lainnya.';
                }

                return Redirect::back()->with([
                    'warning' => $message,
                    'import_errors' => $errorList,
                ]);
            }

            return Redirect::back()->with(['success' => $message]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error Import Karyawan: '.$e->getMessage());

            return Redirect::back()->with(['warning' => $this->failMessage('Gagal import.', $e)]);
        }
    }

    public function monitoringTurnover(Request $request)
    {
        $forcedKodeCabang = $this->getForcedCabang();
        $statusFilter = $this->normalizeStatusFilter($request->get('status_filter'));

        $query = Karyawan::with(['jabatanRel', 'departemen', 'cabang'])
            ->turnover()
            ->orderBy('status_aktif', 'asc')
            ->orderByRaw('CASE WHEN tanggal_keluar IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('tanggal_keluar', 'desc')
            ->orderBy('tanggal_habis_kontrak', 'asc')
            ->orderBy('nama_lengkap', 'asc');

        $query->filterStatus($statusFilter);

        if ($request->filled('nama_karyawan')) {
            $query->where(function ($q) use ($request) {
                $q->where('nama_lengkap', 'ilike', '%'.$request->nama_karyawan.'%')
                    ->orWhere('nik', 'ilike', '%'.$request->nama_karyawan.'%');
            });
        }

        if ($request->filled('kode_dept')) {
            $query->where('kode_dept', $request->kode_dept);
        }

        if (! empty($forcedKodeCabang)) {
            $query->where('kode_cabang', $forcedKodeCabang);
        } elseif ($request->filled('kode_cabang')) {
            $query->where('kode_cabang', $request->kode_cabang);
        }

        $karyawan = $query->paginate(50);

        $departemen = Departemen::orderBy('nama_dept')->get();
        $cabang = ! empty($forcedKodeCabang)
            ? Cabang::where('kode_cabang', $forcedKodeCabang)->get()
            : Cabang::orderBy('nama_cabang', 'asc')->get();

        $statusFilterOptions = $this->getStatusFilterOptions();

        return view('admin.karyawan.turnover', compact('karyawan', 'departemen', 'cabang', 'statusFilterOptions', 'statusFilter'));
    }

    public function updateHistory(Request $request, $nik)
    {
        $tanggalKeluarRequiredStatuses = [Karyawan::STATUS_NONAKTIF, Karyawan::STATUS_DIBERHENTIKAN];

        $request->validate([
            'tmt' => 'nullable|date',
            'tanggal_awal_kontrak' => 'nullable|date',
            'status_aktif' => ['nullable', Rule::in([Karyawan::STATUS_AKTIF, Karyawan::STATUS_NONAKTIF, Karyawan::STATUS_DIBERHENTIKAN])],
            'history_karyawan' => 'nullable|string',
            'statemen' => 'nullable|string',
            'tanggal_habis_kontrak' => 'nullable|date',
            'tanggal_keluar' => [
                'nullable',
                'date',
                Rule::requiredIf(fn () => in_array($request->status_aktif, $tanggalKeluarRequiredStatuses, true)),
            ],
        ], [
            'tanggal_keluar.required' => 'Tanggal keluar wajib diisi jika status karyawan Nonaktif atau Diberhentikan.',
        ]);

        try {
            $karyawan = Karyawan::findOrFail($nik);
            $forcedCabang = $this->getForcedCabang();

            if ($karyawan->status_aktif === Karyawan::STATUS_DIBERHENTIKAN) {
                return Redirect::back()->with(['warning' => 'Data karyawan yang sudah Diberhentikan tidak dapat diubah lagi.']);
            }

            if (! empty($forcedCabang) && $karyawan->kode_cabang !== $forcedCabang) {
                return Redirect::back()->with(['warning' => 'Akses ditolak']);
            }

            $karyawan->tmt = $request->tmt;
            $karyawan->tanggal_awal_kontrak = $request->tanggal_awal_kontrak;
            $karyawan->history_karyawan = $request->history_karyawan;
            $karyawan->statemen = $request->statemen;
            $karyawan->tanggal_habis_kontrak = $request->tanggal_habis_kontrak;
            $karyawan->tanggal_keluar = $request->tanggal_keluar;

            // Auto-reaktivasi: jika karyawan Nonaktif dan tanggal_habis_kontrak diperpanjang ke masa depan
            $newKontrak = $request->tanggal_habis_kontrak;
            $oldStatusDb = $karyawan->getOriginal('status_aktif');
            $oldKontrakDb = $karyawan->getOriginal('tanggal_habis_kontrak');
            if (
                $oldStatusDb === Karyawan::STATUS_NONAKTIF &&
                ! empty($newKontrak) &&
                Carbon::parse($newKontrak)->isFuture() &&
                (empty($oldKontrakDb) || Carbon::parse($newKontrak)->gt(Carbon::parse($oldKontrakDb)))
            ) {
                $karyawan->status_aktif = Karyawan::STATUS_AKTIF;
                $karyawan->tanggal_keluar = null;
            } else {
                $karyawan->status_aktif = $request->status_aktif;
            }

            $karyawan->save();

            return Redirect::back()->with(['success' => 'Histori & Status Berhasil Diupdate']);
        } catch (\Exception $e) {
            return Redirect::back()->with(['warning' => $this->failMessage('Gagal.', $e)]);
        }
    }
}
