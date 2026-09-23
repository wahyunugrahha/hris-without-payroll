<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
use App\Models\Izin;
use App\Models\Presensi;
use App\Services\IzinService;
use App\Services\JadwalKerjaService;
use App\Support\CutiDatesMeta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

/**
 * Daftar, detail, dan hapus pengajuan izin milik karyawan, plus endpoint AJAX kalender.
 * Buat/ubah per jenis ada di App\Http\Controllers\Karyawan\Izin\*.
 */
class PengajuanIzinController extends Controller
{
    private const JENIS_ROUTE = [
        'i' => 'absen',
        's' => 'sakit',
        't' => 'terlambat',
        'p' => 'pulangcepat',
        'c' => 'cuti',
        'r' => 'roster',
    ];

    public function __construct(
        private JadwalKerjaService $jadwalKerja,
        private IzinService $izin,
    ) {}

    public function index(Request $request)
    {
        $bulanInput = $request->input('bulan');
        $tahunInput = $request->input('tahun');
        $bulan = ($bulanInput === null || $bulanInput === '') ? date('m') : $bulanInput;
        $tahun = ($tahunInput === null || $tahunInput === '') ? date('Y') : $tahunInput;
        $nik = Auth::guard('karyawan')->user()->nik;
        $openDetailIzin = null;

        $detailIzinRequest = trim((string) $request->get('detail_izin', ''));
        $showAllHistory = false;
        if ($detailIzinRequest !== '') {
            $exists = Izin::query()
                ->where('nik', $nik)
                ->where('kode_izin', $detailIzinRequest)
                ->exists();

            if ($exists) {
                $openDetailIzin = $detailIzinRequest;
                // Jika datang dari notifikasi (tanpa filter manual), tampilkan seluruh histori dulu.
                if (! $request->filled('bulan') && ! $request->filled('tahun')) {
                    $showAllHistory = true;
                    $bulan = '';
                    $tahun = '';
                }
            }
        }

        $dataIzinQuery = Izin::query()
            ->with('masterCuti')
            ->where('nik', $nik);

        if (! $showAllHistory) {
            if ($bulan !== '') {
                $dataIzinQuery->whereMonth('tgl_izin_dari', $bulan);
            }
            if ($tahun !== '') {
                $dataIzinQuery->whereYear('tgl_izin_dari', $tahun);
            }
        }

        $data_izin = $dataIzinQuery
            ->orderByDesc('tgl_izin_dari')
            ->get();

        foreach ($data_izin as $izin) {
            $effectiveDates = $izin->tanggalDiajukan();

            if (Izin::isMultiDateStatus($izin->status) && count($effectiveDates) > 0) {
                $izin->total_hari_view = count($effectiveDates);
            } else {
                $tglMulai = new \DateTime($izin->tgl_izin_dari);
                $tglAkhir = new \DateTime($izin->tgl_izin_sampai ?? $izin->tgl_izin_dari);
                $izin->total_hari_view = $tglMulai->diff($tglAkhir)->days + 1;
            }

            $izin->keterangan_view = CutiDatesMeta::strip($izin->keterangan);
            $izin->requested_dates_view = [];
            $izin->requested_dates_text = '';
            $izin->requested_dates_compact = '';

            if (Izin::isMultiDateStatus($izin->status) && count($effectiveDates) > 0) {
                $izin->requested_dates_view = collect($effectiveDates)
                    ->map(function ($date) {
                        return date('d-m-Y', strtotime($date));
                    })
                    ->values()
                    ->all();

                $izin->requested_dates_text = implode(', ', $izin->requested_dates_view);
                $izin->requested_dates_compact = CutiDatesMeta::compactText($effectiveDates);
            }
        }

        return view('karyawan.presensi.izin', compact('data_izin', 'bulan', 'tahun', 'openDetailIzin'));
    }

    public function detail($kode_izin)
    {
        try {
            $nik = Auth::guard('karyawan')->user()->nik;

            $izin = Izin::with('masterCuti')
                ->where('kode_izin', $kode_izin)
                ->where('nik', $nik)
                ->first();

            if (! $izin) {
                return response()->json(['error' => 'Data tidak ditemukan'], 404);
            }

            $dari = Carbon::parse($izin->tgl_izin_dari);
            $sampai = Carbon::parse($izin->tgl_izin_sampai ?? $izin->tgl_izin_dari);

            $requestedDates = [];
            if (Izin::isMultiDateStatus($izin->status)) {
                $requestedDates = $izin->tanggalDiajukan();
            } else {
                $period = new \DatePeriod(
                    $dari,
                    new \DateInterval('P1D'),
                    $sampai->copy()->addDay()
                );
                foreach ($period as $dt) {
                    $requestedDates[] = $dt->format('Y-m-d');
                }
            }

            $jumlahHari = Izin::isMultiDateStatus($izin->status) && ! empty($requestedDates)
                ? count($requestedDates)
                : (abs($sampai->diffInDays($dari)) + 1);

            $jenis_badge = '';
            if ($izin->status == 'i') {
                $jenis_badge = '<span class="badge bg-blue-lt">Izin</span>';
            } elseif ($izin->status == 's') {
                $jenis_badge = '<span class="badge bg-pink-lt">Sakit</span>';
            } elseif ($izin->status == 'r') {
                $jenis_badge = '<span class="badge bg-cyan-lt">Roster</span>';
            } elseif (! empty($izin->kode_cuti)) {
                $jenis_badge = '<span class="badge bg-teal-lt">'.e($izin->masterCuti->nama_cuti ?? 'Cuti').'</span>';
            } elseif ($izin->status == 't') {
                $jenis_badge = '<span class="badge bg-orange-lt">Terlambat</span>';
            } elseif ($izin->status == 'p') {
                $jenis_badge = '<span class="badge bg-indigo-lt">Pulang Cepat</span>';
            }

            $status_badge = '';
            if ($izin->status_approved == 1) {
                $status_badge = '<span class="badge bg-success-lt">Disetujui</span>';
            } elseif ($izin->status_approved == 2) {
                $status_badge = '<span class="badge bg-danger-lt">Ditolak</span>';
            } else {
                $status_badge = '<span class="badge bg-warning-lt">Pending</span>';
            }

            $approvedDates = $this->izin->approvedCutiDates(
                $izin->nik,
                $izin->tgl_izin_dari,
                $izin->tgl_izin_sampai,
                $izin->status
            );
            if (Izin::isMultiDateStatus($izin->status) && ! empty($requestedDates)) {
                $approvedDates = array_values(array_intersect($requestedDates, $approvedDates));

                // Fallback aman untuk data lama/inkonsisten: jika status sudah disetujui,
                // tampilkan tanggal yang diajukan sebagai approved agar UI tidak menandai merah semua.
                if ((int) $izin->status_approved === 1 && empty($approvedDates)) {
                    $approvedDates = $requestedDates;
                }
            }

            return response()->json([
                'jenis_badge' => $jenis_badge,
                'jumlah_hari' => $jumlahHari,
                'tgl_dari' => $dari->format('d M Y'),
                'tgl_sampai' => $sampai->format('d M Y'),
                'tgl_dari_iso' => $dari->format('Y-m-d'),
                'tgl_sampai_iso' => $sampai->format('Y-m-d'),
                'keterangan' => CutiDatesMeta::strip($izin->keterangan),
                'status_badge' => $status_badge,
                'is_cuti' => Izin::isMultiDateStatus($izin->status),
                'status' => $izin->status,
                'approved_dates' => $approvedDates,
                'requested_dates' => $requestedDates,
                'status_approved' => (int) $izin->status_approved,
                'catatan_ditolak' => $izin->catatan_ditolak ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $this->failMessage('Gagal memproses data.', $e)], 500);
        }
    }

    public function cekPengajuanIzin(Request $request)
    {
        $tgl_dari = $request->tgl_dari;
        $tgl_sampai = $request->tgl_sampai;
        $nik = Auth::guard('karyawan')->user()->nik;
        $exclude_kode_izin = $request->exclude_kode_izin;

        $cek_presensi_overlap = Presensi::where('nik', $nik)
            ->whereBetween('tgl_presensi', [$tgl_dari, $tgl_sampai])
            ->whereIn('status', ['h', 'i', 's', 'c', 'r'])
            ->exists();

        $query_izin_overlap = Izin::where('nik', $nik)
            ->whereIn('status_approved', [0, 1]);

        if ($exclude_kode_izin) {
            $query_izin_overlap->where('kode_izin', '!=', $exclude_kode_izin);
        }

        $cek_izin_overlap = $query_izin_overlap
            ->where(function ($query) use ($tgl_dari, $tgl_sampai) {
                $query->where('tgl_izin_dari', '<=', $tgl_sampai)
                    ->where('tgl_izin_sampai', '>=', $tgl_dari);
            })
            ->exists();

        if ($cek_presensi_overlap || $cek_izin_overlap) {
            return 1;
        }

        return 0;
    }

    public function getBlacklistDates(Request $request)
    {
        $karyawan = Auth::guard('karyawan')->user();
        $nik = $karyawan->nik;
        $excludeKodeIzin = $request->input('exclude_kode_izin');

        $presensiDates = Presensi::where('nik', $nik)
            ->whereIn('status', ['h', 'i', 's', 'c', 'r'])
            ->pluck('tgl_presensi')
            ->map(function ($date) {
                return $date ? Carbon::parse($date)->format('Y-m-d') : null;
            })
            ->filter()
            ->toArray();

        $izinQuery = Izin::where('nik', $nik)
            ->whereIn('status_approved', [0, 1])
            ->where('kode_izin', '!=', $excludeKodeIzin);

        $izinRecords = $izinQuery->get();

        $izinDates = [];
        foreach ($izinRecords as $record) {
            $izinDates = array_merge($izinDates, $record->tanggalDiajukan());
        }

        $today = date('Y-m-d');
        $oneYearAhead = date('Y-m-d', strtotime('+1 year'));

        $holidayDates = HariLibur::tanggalBerlaku(
            $karyawan->kode_cabang ?? null,
            $karyawan->kode_dept ?? null,
            $today,
            $oneYearAhead
        );

        $shiftLiburDates = [];
        try {
            $begin = new \DateTime($today);
            $end = new \DateTime($oneYearAhead);
            $end->modify('+1 day');
            $daterange = new \DatePeriod($begin, new \DateInterval('P1D'), $end);

            foreach ($daterange as $dt) {
                $d = $dt->format('Y-m-d');
                $namahari = $this->jadwalKerja->namaHari($dt->format('D'));
                [$jkObj, $isLiburShift] = $this->jadwalKerja->untukHari($nik, $karyawan->kode_dept, $karyawan->kode_cabang, $namahari);

                if ($isLiburShift || (strtolower($namahari) === 'minggu' && ! $jkObj)) {
                    $shiftLiburDates[] = $d;
                }
            }
        } catch (\Exception $e) {
        }

        $blacklistDates = array_unique(array_merge($presensiDates, $izinDates, $holidayDates, $shiftLiburDates));

        if ($request->boolean('include_holidays')) {
            return response()->json([
                'blacklist_dates' => array_values($blacklistDates),
                'holiday_dates' => array_values($holidayDates),
            ]);
        }

        return response()->json(array_values($blacklistDates));
    }

    /**
     * Link "Edit" di daftar izin mengarah ke sini; diteruskan ke form edit sesuai jenis pengajuan.
     */
    public function edit(string $kode_izin)
    {
        $izin = Izin::milik(Auth::guard('karyawan')->user()->nik)->find($kode_izin);

        if (! $izin) {
            return Redirect::back()->with('error', 'Data Izin tidak ditemukan.');
        }

        if ($izin->status_approved != 0) {
            return Redirect::back()->with('error', 'Pengajuan ini sudah diverifikasi dan tidak bisa diubah.');
        }

        $jenis = self::JENIS_ROUTE[$izin->status] ?? null;

        return $jenis
            ? redirect()->route("pengajuanizin.editizin{$jenis}", $kode_izin)
            : Redirect::back()->with('error', 'Jenis pengajuan izin tidak valid.');
    }

    public function destroy($kode_izin)
    {
        $nik = Auth::guard('karyawan')->user()->nik;

        $izin = Izin::where('kode_izin', $kode_izin)
            ->where('nik', $nik)
            ->first();

        if (! $izin) {
            return Redirect::back()->with('error', 'Data pengajuan tidak ditemukan atau bukan milik Anda.');
        }

        if ($izin->status_approved != 0) {
            return Redirect::back()->with('error', 'Pengajuan yang sudah diverifikasi tidak dapat dihapus.');
        }

        try {
            // Delete associated document file if exists
            if (! empty($izin->doc_sid) && $izin->doc_sid !== '-') {
                $folderPath = 'uploads/sid';
                Storage::disk('public')->delete($folderPath.'/'.$izin->doc_sid);
            }

            $izin->delete();

            return redirect('/presensi/izin')->with('success', 'Data pengajuan berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect('/presensi/izin')->with('error', $this->failMessage('Gagal menghapus data pengajuan.', $e));
        }
    }
}
