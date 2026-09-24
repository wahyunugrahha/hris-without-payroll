<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveIzinRequest;
use App\Models\Cabang;
use App\Models\Izin;
use App\Models\Jabatan;
use App\Services\IzinApprovalService;
use App\Services\IzinService;
use App\Support\CutiDatesMeta;
use App\Support\WarnaJenis;
use DateInterval;
use DatePeriod;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

/**
 * Daftar & persetujuan pengajuan izin/sakit/cuti/roster/terlambat/pulang cepat oleh admin.
 * Dampak ke presensi diatur IzinApprovalService.
 */
class IzinApprovalController extends Controller
{
    public function __construct(
        private IzinService $izin,
        private IzinApprovalService $approval,
    ) {}

    public function index(Request $request)
    {
        $query = Izin::query()
            ->select(['izin.kode_izin as id', 'izin.tgl_izin_dari', 'izin.tgl_izin_sampai', 'izin.nik', 'karyawan.nama_lengkap', 'karyawan.foto', 'karyawan.kode_cabang', 'izin.status', 'master_cuti.nama_cuti as jenis_cuti_formal', 'izin.kode_cuti', 'izin.status_approved', 'izin.keterangan'])
            ->join('karyawan', 'izin.nik', '=', 'karyawan.nik')
            ->where('karyawan.is_whitelist', 0)
            ->leftJoin('master_cuti', 'izin.kode_cuti', '=', 'master_cuti.kode_cuti')
            ->addSelect('izin.doc_sid')
            ->with('karyawan');

        $isAdminCabang = (bool) Auth::guard('user')->user()?->isAdminCabang();
        $forcedKodeCabang = $this->scopedCabang();

        // Set default filter ke bulan sekarang jika tidak ada filter tanggal yang ditentukan
        if (! $request->filled('bulan') && ! $request->filled('dari') && ! $request->filled('sampai')) {
            $request->merge([
                'bulan' => date('Y-m'),
            ]);
        }

        Carbon::setLocale('id');
        $bulan_indo = '';

        if (! empty($request->bulan) && preg_match('/^\d{4}-\d{2}$/', $request->bulan)) {
            $monthStart = Carbon::createFromFormat('Y-m', $request->bulan)->startOfMonth()->toDateString();
            $monthEnd = Carbon::createFromFormat('Y-m', $request->bulan)->endOfMonth()->toDateString();
            $bulan_indo = Carbon::createFromFormat('Y-m', $request->bulan)->translatedFormat('F Y');
            $query->whereDate('izin.tgl_izin_dari', '<=', $monthEnd)
                ->whereRaw('COALESCE(izin.tgl_izin_sampai, izin.tgl_izin_dari) >= ?', [$monthStart]);
        } elseif ($request->dari && $request->sampai) {
            $query->whereBetween('izin.tgl_izin_dari', [$request->dari, $request->sampai]);
            $bulan_indo = Carbon::parse($request->dari)->translatedFormat('d F Y').' - '.Carbon::parse($request->sampai)->translatedFormat('d F Y');
        }

        if ($request->nik) {
            $query->whereRaw('LOWER(izin.nik) = ?', [strtolower($request->nik)]);
        }
        if ($request->nama_lengkap) {
            $query->whereRaw('LOWER(nama_lengkap) like ?', ['%'.strtolower($request->nama_lengkap).'%']);
        }
        if (in_array($request->status_pengajuan, ['i', 's', 'c', 'r', 't', 'p'])) {
            $query->where('izin.status', $request->status_pengajuan);
        }
        if (in_array($request->status_approved, ['0', '1', '2'])) {
            $query->where('status_approved', $request->status_approved);
        }
        if ($request->kode_cabang) {
            $query->where('karyawan.kode_cabang', $request->kode_cabang);
        }
        if ($forcedKodeCabang !== null) {
            $query->where('karyawan.kode_cabang', $forcedKodeCabang);
        }

        // Filter Jabatan (single)
        if ($request->filled('jabatan_id')) {
            $query->where('karyawan.jabatan_id', $request->jabatan_id);
        }

        $izinsakit = $query->orderBy('status_approved', 'asc')
            ->orderBy('tgl_izin_dari', 'desc')
            ->paginate(25)
            ->appends($request->all());

        foreach ($izinsakit as $izin) {
            $effectiveDates = Izin::isMultiDateStatus($izin->status)
                ? $izin->tanggalDiajukan()
                : CutiDatesMeta::datesFromRange($izin->tgl_izin_dari, $izin->tgl_izin_sampai);

            $izin->total_hari_view = ! empty($effectiveDates)
                ? count($effectiveDates)
                : 1;

            $izin->keterangan_view = CutiDatesMeta::strip($izin->keterangan);
            $izin->requested_dates_view = [];
            $izin->requested_dates_text = '';
            $izin->requested_dates_compact = '';

            if (Izin::isMultiDateStatus($izin->status) && ! empty($effectiveDates)) {
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

        $cabang = Cabang::all();
        $jabatans = Jabatan::whereHas('role', function ($q) {
            $q->where('guard_name', 'karyawan');
        })->orderBy('nama_jabatan')->get();

        return view('admin.presensi.izinsakit', compact('izinsakit', 'isAdminCabang', 'cabang', 'jabatans', 'bulan_indo'));
    }

    public function show(string $id)
    {
        try {
            $izin = Izin::with(['karyawan', 'karyawan.cabang', 'masterCuti'])->find($id);

            if (! $izin) {
                return response()->json(['error' => 'Data tidak ditemukan'], 404);
            }

            if ($this->outsideAdminCabang($izin->karyawan)) {
                return response()->json(['error' => 'Anda tidak memiliki akses ke data cabang lain.'], 403);
            }

            $dari = Carbon::parse($izin->tgl_izin_dari);
            $sampai = Carbon::parse($izin->tgl_izin_sampai ?? $izin->tgl_izin_dari);

            $requestedDates = [];
            if (Izin::isMultiDateStatus($izin->status)) {
                $requestedDates = $izin->tanggalDiajukan();
            } else {
                $period = new DatePeriod(
                    $dari,
                    new DateInterval('P1D'),
                    $sampai->copy()->addDay()
                );
                foreach ($period as $dt) {
                    $requestedDates[] = $dt->format('Y-m-d');
                }
            }

            $jumlahHari = Izin::isMultiDateStatus($izin->status) && ! empty($requestedDates)
                ? count($requestedDates)
                : (abs($sampai->diffInDays($dari)) + 1);

            // Fix: Null Coalescing & Optional Helper for Master Cuti
            $nama_cuti = optional($izin->masterCuti)->nama_cuti ?? 'Cuti';

            $labelJenis = $izin->status === 'c' && ! empty($izin->kode_cuti)
                ? $nama_cuti
                : (['i' => 'Izin', 's' => 'Sakit', 'r' => 'Roster', 't' => 'Izin Terlambat', 'p' => 'Pulang Cepat', 'c' => 'Cuti'][$izin->status] ?? '');
            $jenis_badge = $labelJenis === ''
                ? ''
                : '<span class="tag hue-'.WarnaJenis::ketidakhadiran($izin->status).'">'.e($labelJenis).'</span>';

            $status_badge = '';
            if ($izin->status_approved == 1) {
                $status_badge = '<span class="badge bg-success-lt">Disetujui</span>';
            } elseif ($izin->status_approved == 2) {
                $status_badge = '<span class="badge bg-danger-lt">Ditolak</span>';
            } else {
                $status_badge = '<span class="badge bg-warning-lt">Pending</span>';
            }

            // Menangani Dokumen Lampiran
            $lampiran_link = '';
            $sid_display = '';
            if (! empty($izin->doc_sid)) {
                $sidPath = null;
                if (Storage::disk('public')->exists('uploads/sid/'.$izin->doc_sid)) {
                    $sidPath = asset('storage/uploads/sid/'.$izin->doc_sid);
                } elseif (Storage::disk('public')->exists('public/uploads/sid/'.$izin->doc_sid)) {
                    $sidPath = asset('storage/public/uploads/sid/'.$izin->doc_sid);
                }

                if ($sidPath) {
                    $ext = strtolower(pathinfo($izin->doc_sid, PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp'])) {
                        $sid_display = '<img src="'.$sidPath.'" alt="SID" style="max-width: 100%; max-height: 400px; border-radius: 4px;" />';
                    } else {
                        $lampiran_link = '<a href="'.$sidPath.'" target="_blank" class="btn btn-sm btn-primary">Lihat Lampiran</a>';
                    }
                }
            }

            // Ambil approved dates jika statusnya Cuti
            $approved_dates = [];
            if (Izin::isMultiDateStatus($izin->status)) {
                $approved_dates = $this->izin->approvedCutiDates($izin->nik, $izin->tgl_izin_dari, $izin->tgl_izin_sampai, $izin->status);
                if (! empty($requestedDates)) {
                    $approved_dates = array_values(array_intersect($requestedDates, $approved_dates));
                }
            }

            return response()->json([
                'nama_lengkap' => $izin->karyawan->nama_lengkap,
                'nik' => $izin->nik,
                'cabang_name' => $izin->karyawan->cabang->nama_cabang ?? '-',
                'jabatan' => $izin->karyawan->jabatan_nama ?? '-',
                'jenis_badge' => $jenis_badge,
                'jumlah_hari' => $jumlahHari,
                // Kirim format Display (d M Y) dan Format Standar (Y-m-d)
                'tgl_dari' => $dari->format('d M Y'),
                'tgl_sampai' => $sampai->format('d M Y'),
                'tgl_dari_std' => $dari->format('Y-m-d'), // Digunakan untuk inisialisasi JS Datepicker
                'tgl_sampai_std' => $sampai->format('Y-m-d'),
                'keterangan' => CutiDatesMeta::strip($izin->keterangan),
                'status_badge' => $status_badge,
                'sid_display' => $sid_display,
                'lampiran_link' => $lampiran_link,
                'is_cuti' => Izin::isMultiDateStatus($izin->status),
                'status' => $izin->status,
                'status_approved' => (int) $izin->status_approved,
                'catatan_ditolak' => $izin->catatan_ditolak,
                'approved_dates' => $approved_dates,
                'requested_dates' => Izin::isMultiDateStatus($izin->status) ? $requestedDates : [],
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $this->failMessage('Gagal memproses data.', $e)], 500);
        }
    }

    public function update(ApproveIzinRequest $request)
    {
        $izin = Izin::with('karyawan')->find($request->id_izinsakit_from);

        if (! $izin) {
            return Redirect::back()->with('warning', 'Data tidak ditemukan.');
        }

        if ($this->outsideAdminCabang($izin->karyawan)) {
            return Redirect::back()->with('warning', 'Anda tidak memiliki akses ke data cabang lain.');
        }

        try {
            match ((int) $request->status_approved) {
                IzinApprovalService::DISETUJUI => $this->approval->setujui($izin, $request->tanggalDipilih()),
                IzinApprovalService::DITOLAK => $this->approval->tolak($izin, $request->input('catatan_ditolak')),
                default => $this->approval->kembalikanKePending($izin),
            };

            return Redirect::back()->with('success', 'Data Berhasil Di Update.');
        } catch (BusinessException $e) {
            return Redirect::back()->with('warning', $e->getMessage());
        } catch (Exception $e) {
            return Redirect::back()->with('error', $this->failMessage('Gagal memproses data.', $e));
        }
    }

    public function cancel(string $id)
    {
        $izin = Izin::with('karyawan')->find($id);

        if (! $izin) {
            return Redirect::back()->with('warning', 'Data tidak ditemukan.');
        }

        if ($this->outsideAdminCabang($izin->karyawan)) {
            return Redirect::back()->with('warning', 'Anda tidak memiliki akses ke data cabang lain.');
        }

        try {
            $this->approval->kembalikanKePending($izin);

            return Redirect::back()->with('success', 'Approval dibatalkan.');
        } catch (Exception $e) {
            report($e);

            return Redirect::back()->with('error', 'Gagal membatalkan.');
        }
    }
}
