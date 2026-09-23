<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\Departemen;
use App\Models\Jabatan;
use App\Models\Lembur;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class LemburController extends Controller
{
    /**
     * Helper privat untuk membatasi query database.
     * Jika user yang login adalah Admin Cabang, query otomatis difilter hanya menampilkan
     * data karyawan dari cabang yang sama dengan user tersebut.
     *
     * FIX BUG: Jika cabang dihapus, user masih punya kode_cabang lama tapi tidak ada karyawan terkait.
     * Solusi: Filter berdasarkan kode_cabang di tabel karyawan, bukan hanya whereHas.
     */
    private function getScopedQuery()
    {
        $user = Auth::guard('user')->user();
        $query = Lembur::query();

        // Jika user memiliki role admin cabang atau memiliki kode_cabang
        if ($user && ($user->hasRole('admin cabang') || ! empty($user->kode_cabang))) {
            $kodeCabang = $user->kode_cabang;

            // Filter berdasarkan cabang karyawan (bukan hanya whereHas yang mungkin gagal jika relasi tidak valid)
            $query->whereHas('karyawan', function ($q) use ($kodeCabang) {
                $q->where('kode_cabang', $kodeCabang);
            });
        }

        return $query;
    }

    public function index(Request $request)
    {
        return $this->approval($request);
    }

    public function approval(Request $request)
    {
        $loggedInUser = Auth::guard('user')->user();
        $hasRoleAdminCabang = $loggedInUser && $loggedInUser->roles->pluck('name')->contains('admin cabang');

        // Menggunakan scoped query dan eager loading untuk performa
        $query = $this->getScopedQuery()->with(['karyawan.departemen', 'karyawan.cabang']);

        // Filter berdasarkan status approval, tanggal, dan departemen
        if ($request->filled('status')) {
            $query->where('status_approved', $request->status);
        }
        if ($request->filled('tanggal')) {
            $tanggal = $request->tanggal;

            // Konversi format dd-mm-yyyy ke Y-m-d jika diperlukan
            if (strpos($tanggal, '-') !== false && strlen($tanggal) == 10) {
                $parts = explode('-', $tanggal);
                if (count($parts) == 3 && strlen($parts[2]) == 4) {
                    $tanggal = $parts[2].'-'.$parts[1].'-'.$parts[0];
                }
            }
            $query->whereDate('tanggal_lembur', $tanggal);
        }
        if ($request->filled('kode_dept')) {
            $query->whereHas('karyawan', fn ($q) => $q->where('kode_dept', $request->kode_dept));
        }

        // Filter Jabatan (single)
        if ($request->filled('jabatan_id')) {
            $query->whereHas('karyawan', fn ($q) => $q->where('jabatan_id', $request->jabatan_id));
        }

        // Filter Cabang - hanya untuk Super Admin yang ingin filter cabang spesifik
        // Admin Cabang sudah ter-filter otomatis di getScopedQuery()
        if (! $hasRoleAdminCabang && $request->filled('kode_cabang')) {
            $query->whereHas('karyawan', fn ($q) => $q->where('kode_cabang', $request->kode_cabang));
        }

        // Pencarian data berdasarkan NIK atau Nama (Case Insensitive untuk kompatibilitas PostgreSQL)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(nik) like ?', ['%'.strtolower($search).'%'])
                    ->orWhereHas('karyawan', function ($subq) use ($search) {
                        $subq->whereRaw('LOWER(nama_lengkap) like ?', ['%'.strtolower($search).'%']);
                    });
            });
        }

        $lemburs = $query->orderBy('status_approved')->orderByDesc('tanggal_lembur')->paginate(25)->withQueryString();
        $departemen = Departemen::orderBy('nama_dept')->get();

        // Tentukan cabang yang ditampilkan di dropdown
        $forcedKodeCabang = $hasRoleAdminCabang && ! empty($loggedInUser->kode_cabang) ? $loggedInUser->kode_cabang : null;
        $cabang = $hasRoleAdminCabang && $forcedKodeCabang
            ? Cabang::where('kode_cabang', $forcedKodeCabang)->get()
            : Cabang::orderBy('nama_cabang')->get();

        $jabatans = Jabatan::whereHas('role', function ($q) {
            $q->where('guard_name', 'karyawan');
        })->orderBy('nama_jabatan')->get();

        return view('admin.lembur.approval', compact('lemburs', 'departemen', 'cabang', 'jabatans'));
    }

    public function approve(Request $request)
    {
        try {
            // Menggunakan getScopedQuery()->find() untuk mencegah user meng-approve data cabang lain (IDOR Protection)
            $lembur = $this->getScopedQuery()->find($request->id);

            if (! $lembur) {
                return Redirect::back()->with('error', 'Data lembur tidak ditemukan atau akses ditolak.');
            }

            $lembur->update(['status_approved' => 1]); // Status 1 = Disetujui

            return Redirect::back()->with('success', 'Lembur berhasil disetujui.');

        } catch (\Exception $e) {
            return Redirect::back()->with('error', $this->failMessage('Gagal approve.', $e));
        }
    }

    public function reject(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'catatan' => 'nullable|string|max:500',
        ]);

        try {
            // Validasi kepemilikan data sebelum reject
            $lembur = $this->getScopedQuery()->find($request->id);

            if (! $lembur) {
                return Redirect::back()->with('error', 'Data tidak ditemukan atau akses ditolak.');
            }

            $lembur->update([
                'status_approved' => 2, // Status 2 = Ditolak
                'keterangan' => $request->catatan,
            ]);

            return Redirect::back()->with('success', 'Lembur berhasil ditolak.');

        } catch (\Exception $e) {
            return Redirect::back()->with('error', $this->failMessage('Gagal reject.', $e));
        }
    }

    public function cancel(Request $request)
    {
        try {
            $lembur = $this->getScopedQuery()->find($request->id);

            if (! $lembur) {
                return Redirect::back()->with('error', 'Data tidak ditemukan atau akses ditolak.');
            }

            $lembur->update(['status_approved' => 0]); // Status 0 = Pending

            return Redirect::back()->with('success', 'Approval dibatalkan.');

        } catch (\Exception $e) {
            return Redirect::back()->with('error', $this->failMessage('Gagal batal.', $e));
        }
    }

    public function updateJam(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i'],
        ], [
            'jam_mulai.required' => 'Jam mulai wajib diisi.',
            'jam_mulai.date_format' => 'Format jam mulai tidak valid.',
            'jam_selesai.required' => 'Jam selesai wajib diisi.',
            'jam_selesai.date_format' => 'Format jam selesai tidak valid.',
        ]);

        try {
            $lembur = $this->getScopedQuery()->find($request->id);

            if (! $lembur) {
                return Redirect::back()->with('error', 'Data lembur tidak ditemukan atau akses ditolak.');
            }

            if ((int) $lembur->status_approved !== 0) {
                return Redirect::back()->with('error', 'Jam lembur hanya dapat diedit saat status masih Menunggu.');
            }

            $tanggal = Carbon::parse($lembur->tanggal_lembur)->format('Y-m-d');
            $waktuMulai = Carbon::parse($tanggal.' '.$request->jam_mulai);
            $waktuSelesai = Carbon::parse($tanggal.' '.$request->jam_selesai);

            if ($waktuSelesai->lt($waktuMulai)) {
                $waktuSelesai->addDay();
            }

            $totalJam = round($waktuMulai->floatDiffInHours($waktuSelesai), 2);

            $oldJamMulai = substr((string) $lembur->jam_mulai, 0, 5);
            $oldJamSelesai = substr((string) $lembur->jam_selesai, 0, 5);
            $isChanged = ($oldJamMulai !== $request->jam_mulai) || ($oldJamSelesai !== $request->jam_selesai);

            $updateData = [
                'jam_mulai' => $request->jam_mulai,
                'jam_selesai' => $request->jam_selesai,
                'total_jam' => $totalJam,
            ];

            if ($isChanged) {
                if (! empty($lembur->jam_selesai) && $oldJamSelesai !== $request->jam_selesai && empty($lembur->jam_selesai_awal)) {
                    $updateData['jam_selesai_awal'] = $lembur->jam_selesai;
                }

                $updateData['update_count'] = (int) $lembur->update_count + 1;
                $updateData['last_update_at'] = now();
            }

            $lembur->update($updateData);

            return Redirect::back()->with('success', 'Jam lembur berhasil diperbarui.');
        } catch (\Exception $e) {
            return Redirect::back()->with('error', $this->failMessage('Gagal memperbarui jam lembur.', $e));
        }
    }

    public function rekap(Request $request)
    {
        $namabulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        $list_periode = [];
        for ($i = 1; $i <= 12; $i++) {
            $bulan_lalu = $i == 1 ? 12 : $i - 1;
            $nama_bln_lalu = $namabulan[$bulan_lalu];
            $nama_bln_ini = $namabulan[$i];

            $list_periode[$i] = "26 $nama_bln_lalu - 25 $nama_bln_ini";
        }

        $hariIni = Carbon::now();
        if ($hariIni->day >= 26) {
            $hariIni->addMonth();
        }
        $defaultBulan = $hariIni->format('n');
        $defaultTahun = $hariIni->format('Y');

        $departemen = Departemen::orderBy('nama_dept')->get();

        $loggedInUser = Auth::guard('user')->user();
        $hasRoleAdminCabang = $loggedInUser && $loggedInUser->roles->pluck('name')->contains('admin cabang');

        $forcedKodeCabang = $hasRoleAdminCabang && ! empty($loggedInUser->kode_cabang) ? $loggedInUser->kode_cabang : null;
        $cabang = $hasRoleAdminCabang
            ? Cabang::where('kode_cabang', $forcedKodeCabang)->get()
            : Cabang::orderBy('nama_cabang')->get();

        return view('admin.lembur.rekap', compact('namabulan', 'list_periode', 'defaultBulan', 'defaultTahun', 'departemen', 'cabang', 'forcedKodeCabang'));
    }

    public function cetakrekap(Request $request)
    {
        $request->validate([
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer',
        ]);

        $loggedInUser = Auth::guard('user')->user();
        $hasRoleAdminCabang = $loggedInUser && $loggedInUser->roles->pluck('name')->contains('admin cabang');

        // Penentuan kode cabang: Jika admin cabang, dipaksa kode sendiri. Jika super admin, ambil dari request.
        $filterCabang = $hasRoleAdminCabang && ! empty($loggedInUser->kode_cabang)
            ? $loggedInUser->kode_cabang
            : $request->kode_cabang;

        // Hitung periode: Tanggal 26 bulan sebelumnya sampai tanggal 25 bulan yang dipilih
        $bulan = $request->bulan;
        $tahun = $request->tahun;

        // Tanggal mulai: Tanggal 26 bulan sebelumnya
        if ($bulan == 1) {
            $bulanSebelum = 12;
            $tahunSebelum = $tahun - 1;
        } else {
            $bulanSebelum = $bulan - 1;
            $tahunSebelum = $tahun;
        }

        $tanggalMulai = sprintf('%04d-%02d-26', $tahunSebelum, $bulanSebelum);
        $tanggalSelesai = sprintf('%04d-%02d-25', $tahun, $bulan);

        // Query data lembur yang sudah disetujui (status 1)
        $query = Lembur::with('karyawan')
            ->whereBetween('tanggal_lembur', [$tanggalMulai, $tanggalSelesai])
            ->where('status_approved', 1);

        if ($filterCabang) {
            $query->whereHas('karyawan', fn ($q) => $q->where('kode_cabang', $filterCabang));
        }
        if ($request->kode_dept) {
            $query->whereHas('karyawan', fn ($q) => $q->where('kode_dept', $request->kode_dept));
        }

        $lembur = $query->get()->groupBy('nik');
        $cabang = $filterCabang ? Cabang::where('kode_cabang', $filterCabang)->first() : null;

        if ($request->has('exportexcel')) {
            $filename = 'Rekap_Lembur_'.$request->bulan.'_'.$request->tahun.'.xls';

            return response()->view('admin.lembur.cetakrekapexcel', [
                'lembur' => $lembur,
                'bulan' => $request->bulan,
                'tahun' => $request->tahun,
                'cabang' => $cabang,
            ])->header('Content-Type', 'application/vnd.ms-excel')
                ->header('Content-Disposition', 'attachment; filename='.$filename);
        }

        return view('admin.lembur.cetakrekap', [
            'lembur' => $lembur,
            'bulan' => $request->bulan,
            'tahun' => $request->tahun,
            'cabang' => $cabang,
        ]);
    }
}
