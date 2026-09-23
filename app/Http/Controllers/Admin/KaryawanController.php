<?php

namespace App\Http\Controllers\Admin;

use App\Exports\KaryawanExport;
use App\Exports\KaryawanTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\KaryawanRequest;
use App\Imports\KaryawanImport;
use App\Models\Cabang;
use App\Models\Departemen;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Services\FotoKaryawanService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class KaryawanController extends Controller
{
    public function __construct(private FotoKaryawanService $foto) {}

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
        $forcedKodeCabang = $this->scopedCabang();
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

    public function store(KaryawanRequest $request)
    {
        $data = $request->dataKaryawan();
        // Admin cabang selalu menambah karyawan ke cabangnya sendiri.
        $data['kode_cabang'] = $this->scopedCabang() ?: $data['kode_cabang'];
        $data['is_whitelist'] = $request->boolean('is_whitelist');
        $data['password'] = Hash::make('123456'); // Default password
        $data['status_aktif'] = Karyawan::STATUS_AKTIF;

        foreach (KaryawanRequest::FIELD_FOTO as $jenis) {
            $data[$jenis] = $this->foto->namaBaru($jenis, $request->file($jenis), null, $data['nik'], $data['nik']);
        }

        try {
            DB::transaction(function () use ($request, $data) {
                $karyawan = Karyawan::create($data);
                $this->sinkronRoleJabatan($karyawan);

                foreach (KaryawanRequest::FIELD_FOTO as $jenis) {
                    $this->foto->terapkan($jenis, $request->file($jenis), null, $data[$jenis]);
                }
            });

            return Redirect::back()->with(['success' => 'Data Karyawan Berhasil Disimpan']);
        } catch (\Exception $e) {
            return Redirect::back()->with(['warning' => $this->failMessage('Gagal disimpan.', $e)])->withInput();
        }
    }

    public function edit($nik)
    {
        try {
            $karyawan = Karyawan::findOrFail($nik);
            $forcedCabang = $this->scopedCabang();

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

    public function update(KaryawanRequest $request, $nik)
    {
        $karyawan = Karyawan::findOrFail($nik);

        if ($karyawan->status_aktif === Karyawan::STATUS_DIBERHENTIKAN) {
            return Redirect::back()->with(['warning' => 'Karyawan yang sudah Diberhentikan tidak dapat diedit.']);
        }

        if ($this->outsideAdminCabang($karyawan)) {
            return Redirect::back()->with(['warning' => 'Anda tidak berhak mengedit data cabang lain.']);
        }

        $payload = $request->dataKaryawan();
        $payload['kode_cabang'] = $this->scopedCabang() ?: $payload['kode_cabang'];
        $payload['is_whitelist'] = $request->boolean('is_whitelist');

        if ($request->filled('password')) {
            $payload['password'] = Hash::make($request->password);
        }

        if ($karyawan->diaktifkanKembaliOleh($payload['tanggal_habis_kontrak'] ?? null)) {
            $payload['status_aktif'] = Karyawan::STATUS_AKTIF;
            $payload['tanggal_keluar'] = null;
        }

        $fotoLama = $karyawan->only(KaryawanRequest::FIELD_FOTO);
        foreach (KaryawanRequest::FIELD_FOTO as $jenis) {
            $payload[$jenis] = $this->foto->namaBaru($jenis, $request->file($jenis), $fotoLama[$jenis], $karyawan->nik, $payload['nik']);
        }

        try {
            DB::transaction(function () use ($request, $nik, $payload, $fotoLama) {
                // Update via query agar perubahan NIK (primary key) ikut ter-cascade di database.
                Karyawan::where('nik', $nik)->update($payload);
                $this->sinkronRoleJabatan(Karyawan::findOrFail($payload['nik']));

                foreach (KaryawanRequest::FIELD_FOTO as $jenis) {
                    $this->foto->terapkan($jenis, $request->file($jenis), $fotoLama[$jenis], $payload[$jenis]);
                }
            });

            return Redirect::back()->with(['success' => 'Data Karyawan Berhasil Diupdate']);
        } catch (QueryException $e) {
            if ($e->getCode() == '23503') { // Postgres Foreign Key Violation
                return Redirect::back()->with(['warning' => 'Gagal Update NIK: Data terkunci relasi data lain.'])->withInput();
            }

            return Redirect::back()->with(['warning' => $this->failMessage('Gagal memproses data.', $e)])->withInput();
        } catch (\Exception $e) {
            return Redirect::back()->with(['warning' => $this->failMessage('Gagal memproses data.', $e)])->withInput();
        }
    }

    /**
     * Role karyawan mengikuti role jabatannya.
     */
    private function sinkronRoleJabatan(Karyawan $karyawan): void
    {
        $role = Jabatan::with('role')->find($karyawan->jabatan_id)?->role;

        if ($role) {
            $karyawan->syncRoles([$role->name]);
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
        $karyawan = Karyawan::findOrFail($nik);

        if ($this->outsideAdminCabang($karyawan)) {
            return Redirect::back()->with(['warning' => 'Akses Ditolak.']);
        }

        try {
            DB::transaction(fn () => $karyawan->delete());
        } catch (QueryException $e) {
            return Redirect::back()->with(['warning' => $e->getCode() == '23503'
                ? 'Tidak bisa dihapus: Karyawan memiliki riwayat data (Presensi/Izin/dll).'
                : 'Gagal menghapus data (Database Error).']);
        }

        foreach (KaryawanRequest::FIELD_FOTO as $jenis) {
            $this->foto->hapus($jenis, $karyawan->{$jenis});
        }

        return Redirect::route('karyawan.index')->with(['success' => 'Data Berhasil Dihapus']);
    }

    public function export(Request $request)
    {
        $forcedCabang = $this->scopedCabang();

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
        $forcedKodeCabang = $this->scopedCabang();
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
            $forcedCabang = $this->scopedCabang();

            if ($karyawan->status_aktif === Karyawan::STATUS_DIBERHENTIKAN) {
                return Redirect::back()->with(['warning' => 'Data karyawan yang sudah Diberhentikan tidak dapat diubah lagi.']);
            }

            if (! empty($forcedCabang) && $karyawan->kode_cabang !== $forcedCabang) {
                return Redirect::back()->with(['warning' => 'Akses ditolak']);
            }

            $diaktifkanKembali = $karyawan->diaktifkanKembaliOleh($request->tanggal_habis_kontrak);

            $karyawan->tmt = $request->tmt;
            $karyawan->tanggal_awal_kontrak = $request->tanggal_awal_kontrak;
            $karyawan->history_karyawan = $request->history_karyawan;
            $karyawan->statemen = $request->statemen;
            $karyawan->tanggal_habis_kontrak = $request->tanggal_habis_kontrak;
            $karyawan->tanggal_keluar = $request->tanggal_keluar;

            if ($diaktifkanKembali) {
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
