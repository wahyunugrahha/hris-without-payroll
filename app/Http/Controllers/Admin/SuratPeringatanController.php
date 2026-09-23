<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\SuratPeringatan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SuratPeringatanController extends Controller
{
    public function index(Request $request)
    {
        $loggedInUser = Auth::guard('user')->user();
        $hasRoleAdminCabang = (bool) $loggedInUser?->isAdminCabang();
        $forcedKodeCabang = $hasRoleAdminCabang && ! empty($loggedInUser->kode_cabang) ? $loggedInUser->kode_cabang : null;

        // 1. Base Query menggunakan Scope Keamanan
        // Eager load Cabang dan Departemen melalui Karyawan
        $baseQuery = SuratPeringatan::visibleTo(Auth::guard('user')->user())->with(['karyawan.cabang', 'karyawan.departemen']);

        // 2. Ambil Statistik Ringkas (Selalu total global aktif)
        $stats = [
            'total_aktif' => (clone $baseQuery)->whereDate('expires_at', '>', Carbon::today())->count(),
            'sp1_aktif' => (clone $baseQuery)->where('level', 1)->whereDate('expires_at', '>', Carbon::today())->count(),
            'sp2_aktif' => (clone $baseQuery)->where('level', 2)->whereDate('expires_at', '>', Carbon::today())->count(),
            'sp3_aktif' => (clone $baseQuery)->where('level', 3)->whereDate('expires_at', '>', Carbon::today())->count(),
        ];

        // 3. Query Data Table
        $query = $baseQuery->orderBy('expires_at', 'desc');

        // Filter Level (SP1, SP2, SP3)
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        // Filter Status (Aktif/Expired)
        if ($request->filled('status')) {
            if ($request->status == 'active') {
                $query->whereDate('expires_at', '>', Carbon::today());
            } elseif ($request->status == 'expired') {
                $query->whereDate('expires_at', '<=', Carbon::today());
            }
        }

        // Filter Pencarian
        if ($request->filled('q')) {
            $q = $request->get('q');
            $query->whereHas('karyawan', function ($qk) use ($q) {
                $qk->whereRaw('LOWER(nama_lengkap) ilike ?', ['%'.strtolower($q).'%'])
                    ->orWhereRaw('LOWER(nik) ilike ?', ['%'.strtolower($q).'%']);
            });
        }

        // Filter Cabang (Untuk Super Admin yang ingin filter manual)
        // Jika Admin Cabang, forcedKodeCabang sudah di-handle oleh scope visibleTo()
        if (! $forcedKodeCabang && $request->filled('kode_cabang')) {
            $query->whereHas('karyawan', function ($qk) use ($request) {
                $qk->where('kode_cabang', $request->kode_cabang);
            });
        }

        // Filter Jabatan (single)
        if ($request->filled('jabatan_id')) {
            $query->whereHas('karyawan', function ($qk) use ($request) {
                $qk->where('jabatan_id', $request->jabatan_id);
            });
        }

        $items = $query->paginate(20)->withQueryString();

        $cabang = $hasRoleAdminCabang
            ? Cabang::where('kode_cabang', $forcedKodeCabang)->get()
            : Cabang::orderBy('nama_cabang')->get();

        $karyawanQuery = Karyawan::query()->orderBy('nama_lengkap');

        if ($forcedKodeCabang) {
            $karyawanQuery->where('kode_cabang', $forcedKodeCabang);
        }
        $karyawanList = $karyawanQuery->get(['nik', 'nama_lengkap', 'jabatan_id']);

        $jabatans = Jabatan::whereHas('role', function ($q) {
            $q->where('guard_name', 'karyawan');
        })->orderBy('nama_jabatan')->get();

        // Jangan lupa pass 'karyawanList' ke view
        return view('admin.surat_peringatan.index', compact('items', 'stats', 'cabang', 'karyawanList', 'jabatans'));
    }

    public function store(Request $request)
    {
        $user = Auth::guard('user')->user();
        $forcedKodeCabang = $user?->scopedCabang();

        try {
            $validated = $request->validate([
                'nik' => [
                    'required',
                    'string',
                    // Validasi: Pastikan NIK ada DAN milik cabang user (jika admin cabang)
                    Rule::exists('karyawan', 'nik')->where(function ($query) use ($forcedKodeCabang) {
                        if ($forcedKodeCabang) {
                            $query->where('kode_cabang', $forcedKodeCabang);
                        }
                    }),
                ],
                'level' => 'required|integer|in:1,2,3',
                'violation_type' => 'required|string|in:late,absent,discipline,other',
                'issued_at' => 'required|date',
                'expires_at' => 'required|date|after:issued_at',
                'note' => 'nullable|string|max:1000',
            ], [
                'nik.exists' => 'NIK tidak ditemukan atau bukan karyawan cabang Anda.',
                'expires_at.after' => 'Tanggal berakhir harus setelah tanggal terbit.',
            ]);

            // Cek SP aktif saat ini
            $activeSP = SuratPeringatan::where('nik', $validated['nik'])
                ->whereDate('expires_at', '>', Carbon::today())
                ->orderByDesc('level')
                ->first();

            if ($activeSP && $validated['level'] < $activeSP->level) {
                return back()->with('error', "Gagal: Karyawan masih memiliki SP {$activeSP->level} yang aktif. Tidak bisa menambahkan SP.")->withInput();
            }

            // Deaktivasi SP lama (hanya jika level baru >= level lama)
            if ($activeSP) {
                SuratPeringatan::where('nik', $validated['nik'])
                    ->whereDate('expires_at', '>', Carbon::today())
                    ->update(['expires_at' => Carbon::yesterday()]);
            }

            SuratPeringatan::create([
                'nik' => $validated['nik'],
                'level' => $validated['level'],
                'violation_type' => $validated['violation_type'],
                'issued_at' => $validated['issued_at'],
                'expires_at' => $validated['expires_at'],
                'note' => $validated['note'] ?? null,
            ]);

            return redirect()->route('suratperingatan.index')
                ->with('success', 'Surat Peringatan berhasil ditambahkan');

        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return back()->with('error', $this->failMessage('Gagal menambahkan Surat Peringatan.', $e));
        }
    }

    public function pemutihan($id)
    {
        try {
            $sp = SuratPeringatan::visibleTo(Auth::guard('user')->user())->find($id);

            if (! $sp) {
                return back()->with('error', 'Data tidak ditemukan atau akses ditolak.');
            }

            $userName = Auth::guard('user')->user()->name ?? 'Admin';
            $cleanNote = preg_replace('/\s*\[DIPUTIHKAN MANUAL.*?\]/i', '', $sp->note);
            $cleanNote = rtrim($cleanNote);
            if (str_ends_with($cleanNote, '].')) {
                $cleanNote = rtrim($cleanNote, '.');
            }

            $sp->update([
                'expires_at' => Carbon::now()->subDay(), // Expired kemarin
                'note' => $cleanNote.' [DIPUTIHKAN MANUAL OLEH '.strtoupper($userName).' PADA '.now()->format('d-m-Y H:i').']',
            ]);

            return back()->with('success', 'Surat Peringatan berhasil diputihkan (Non-Aktif).');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses data.');
        }
    }

    public function cetak($id)
    {
        $sp = SuratPeringatan::visibleTo(Auth::guard('user')->user())->with('karyawan')->find($id);

        if (! $sp) {
            return back()->with('error', 'Data tidak ditemukan atau akses ditolak.');
        }

        $issued = Carbon::parse($sp->issued_at ?? now());
        $formattedIssued = $this->formatIndoDate($issued);

        return view('admin.surat_peringatan.print', compact('sp', 'formattedIssued'));
    }

    public function checkActive($nik)
    {
        $hasActiveSP = SuratPeringatan::where('nik', $nik)
            ->whereDate('expires_at', '>', Carbon::today())
            ->orderByDesc('level')
            ->first();

        if ($hasActiveSP) {
            return response()->json([
                'active' => true,
                'level' => $hasActiveSP->level,
                'expires_at' => $hasActiveSP->expires_at->format('d-m-Y'),
            ]);
        }

        return response()->json(['active' => false]);
    }

    private function formatIndoDate($date)
    {
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return $date->format('d').' '.($months[(int) $date->format('m')] ?? $date->format('F')).' '.$date->format('Y');
    }
}
