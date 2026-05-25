<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use App\Models\Izin;
use App\Models\HariLibur;
use App\Models\MasterCuti;
use App\Models\Presensi;
use App\Models\Setjamkerja;
use App\Models\KonfigurasiJkDeptDetail;
use App\Models\SuratPeringatan;
use Carbon\Carbon;

class PengajuanIzinController extends Controller
{
    private const CUTI_DATES_META_PREFIX = '[CUTI_DATES:';

    private function isMultiDateStatus(?string $status): bool
    {
        return in_array($status, ['c', 'r'], true);
    }

    private function getPengajuanTypeLabel(?string $status, ?Izin $izin = null): string
    {
        if ($status === 'r') {
            return 'Roster';
        }

        if ($status === 'c') {
            return $izin?->masterCuti->nama_cuti ?? 'Cuti';
        }

        return 'Pengajuan';
    }

    private function getApprovedCutiDates($nik, $dateFrom, $dateTo, ?string $izinStatus = 'c')
    {
        $approvedStatuses = ['c'];
        if ($izinStatus === 'r') {
            $approvedStatuses = ['r'];
        }

        return Presensi::where('nik', $nik)
            ->whereIn('status', $approvedStatuses)
            ->whereBetween('tgl_presensi', [$dateFrom, $dateTo])
            ->orderBy('tgl_presensi')
            ->pluck('tgl_presensi')
            ->map(function ($date) {
                if (empty($date)) {
                    return null;
                }

                return date('Y-m-d', strtotime((string) $date));
            })
            ->filter(function ($date) {
                return $date !== null;
            })
            ->unique()
            ->values()
            ->toArray();
    }

    private function parseCutiDatesMeta(?string $keterangan): array
    {
        if (empty($keterangan)) {
            return [];
        }

        $metaContent = null;
        if (preg_match('/\[CUTI_DATES:([^\]]*)\]?/i', $keterangan, $matches)) {
            $metaContent = $matches[1] ?? '';
        }

        // Fallback untuk data lama/format rusak: ambil semua tanggal ISO dari keterangan.
        if ($metaContent === null) {
            preg_match_all('/\d{4}-\d{2}-\d{2}/', $keterangan, $allDateMatches);
            $metaContent = implode(',', $allDateMatches[0] ?? []);
        }

        return collect(explode(',', (string) $metaContent))
            ->map(function ($date) {
                return trim($date);
            })
            ->filter(function ($date) {
                return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
            })
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function buildCompactDateSegmentsText(array $isoDates): string
    {
        $dates = collect($isoDates)
            ->filter(function ($date) {
                return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date);
            })
            ->unique()
            ->sort()
            ->values()
            ->all();

        if (empty($dates)) {
            return '';
        }

        $segments = [];
        $segmentStart = $dates[0];
        $segmentEnd = $dates[0];

        for ($i = 1; $i < count($dates); $i++) {
            $current = $dates[$i];
            $nextExpected = date('Y-m-d', strtotime($segmentEnd . ' +1 day'));

            if ($current === $nextExpected) {
                $segmentEnd = $current;
                continue;
            }

            $segments[] = [$segmentStart, $segmentEnd];
            $segmentStart = $current;
            $segmentEnd = $current;
        }
        $segments[] = [$segmentStart, $segmentEnd];

        $format = function ($date) {
            return date('d-m-Y', strtotime($date));
        };

        return collect($segments)->map(function ($segment) use ($format) {
            [$start, $end] = $segment;
            if ($start === $end) {
                return $format($start);
            }
            return $format($start) . ' s/d ' . $format($end);
        })->implode(', ');
    }

    private function stripCutiDatesMeta(?string $keterangan): string
    {
        if (empty($keterangan)) {
            return '';
        }

        $clean = preg_replace('/\s*\[CUTI_DATES:[^\]]*\]?\s*/i', ' ', $keterangan);
        $clean = preg_replace('/\s*\|\s*$/', '', (string) $clean);
        return trim(preg_replace('/\s{2,}/', ' ', (string) $clean));
    }

    private function appendCutiDatesMeta(string $keterangan, $selectedDates): string
    {
        $cleanKeterangan = $this->stripCutiDatesMeta($keterangan);
        $dates = $selectedDates instanceof \Illuminate\Support\Collection
            ? $selectedDates->all()
            : (array) $selectedDates;

        if (empty($dates)) {
            return $cleanKeterangan;
        }

        $meta = self::CUTI_DATES_META_PREFIX . implode(',', $dates) . ']';
        return trim($cleanKeterangan . ' ' . $meta);
    }

    private function getDatesFromRange($fromDate, $toDate): array
    {
        if (empty($fromDate)) {
            return [];
        }

        $result = [];
        try {
            $start = new \DateTime($fromDate);
            $end = new \DateTime($toDate ?: $fromDate);
            if ($end < $start) {
                $end = new \DateTime($fromDate);
            }

            $period = new \DatePeriod($start, new \DateInterval('P1D'), (clone $end)->modify('+1 day'));
            foreach ($period as $date) {
                $result[] = $date->format('Y-m-d');
            }
        } catch (\Exception $e) {
            return [];
        }

        return $result;
    }

    private function getEffectiveIzinDates($izin): array
    {
        if ($this->isMultiDateStatus($izin->status)) {
            $metaDates = $this->parseCutiDatesMeta($izin->keterangan);
            if (!empty($metaDates)) {
                return $metaDates;
            }
        }

        return $this->getDatesFromRange($izin->tgl_izin_dari, $izin->tgl_izin_sampai);
    }

    private function hasIzinDateConflict($nik, \Illuminate\Support\Collection $selectedDates, $excludeKodeIzin = null): bool
    {
        if ($selectedDates->isEmpty()) {
            return false;
        }

        $query = Izin::query()
            ->where('nik', $nik)
            ->whereIn('status_approved', [0, 1])
            ->where(function ($q) use ($selectedDates) {
                $q->where('tgl_izin_dari', '<=', $selectedDates->last())
                    ->where('tgl_izin_sampai', '>=', $selectedDates->first());
            });

        if (!empty($excludeKodeIzin)) {
            $query->where('kode_izin', '!=', $excludeKodeIzin);
        }

        $existingIzins = $query->get();
        $selectedLookup = array_flip($selectedDates->all());

        foreach ($existingIzins as $existingIzin) {
            foreach ($this->getEffectiveIzinDates($existingIzin) as $existingDate) {
                if (isset($selectedLookup[$existingDate])) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getHariLiburDatesByScope($kodeCabang = null, $kodeDept = null, $dateFrom = null, $dateTo = null)
    {
        $query = HariLibur::query();

        if (!empty($dateFrom) && !empty($dateTo)) {
            $query->whereBetween('tanggal_libur', [$dateFrom, $dateTo]);
        }

        $query->where(function ($query) use ($kodeCabang, $kodeDept) {
            $query->where(function ($q) {
                $q->where(function ($c) {
                    $c->whereNull('kode_cabang')
                        ->orWhere('kode_cabang', '')
                        ->orWhere('kode_cabang', 'Semua Cabang');
                })->where(function ($d) {
                    $d->whereNull('kode_dept')
                        ->orWhere('kode_dept', '')
                        ->orWhere('kode_dept', 'Semua Departemen');
                });
            });

            if (!empty($kodeCabang)) {
                $query->orWhere(function ($q) use ($kodeCabang) {
                    $q->where(function ($c) use ($kodeCabang) {
                        $c->where('kode_cabang', $kodeCabang)
                            ->orWhereRaw("concat(',', kode_cabang, ',') like ?", ["%,{$kodeCabang},%"]);
                    })->where(function ($d) {
                        $d->whereNull('kode_dept')
                            ->orWhere('kode_dept', '')
                            ->orWhere('kode_dept', 'Semua Departemen');
                    });
                });
            }

            if (!empty($kodeDept)) {
                $query->orWhere(function ($q) use ($kodeDept) {
                    $q->where(function ($d) use ($kodeDept) {
                        $d->where('kode_dept', $kodeDept)
                            ->orWhereRaw("concat(',', kode_dept, ',') like ?", ["%,{$kodeDept},%"]);
                    })->where(function ($c) {
                        $c->whereNull('kode_cabang')
                            ->orWhere('kode_cabang', '')
                            ->orWhere('kode_cabang', 'Semua Cabang');
                    });
                });
            }

            if (!empty($kodeCabang) && !empty($kodeDept)) {
                $query->orWhere(function ($q) use ($kodeCabang, $kodeDept) {
                    $q->where(function ($c) use ($kodeCabang) {
                        $c->where('kode_cabang', $kodeCabang)
                            ->orWhereRaw("concat(',', kode_cabang, ',') like ?", ["%,{$kodeCabang},%"]);
                    })->where(function ($d) use ($kodeDept) {
                        $d->where('kode_dept', $kodeDept)
                            ->orWhereRaw("concat(',', kode_dept, ',') like ?", ["%,{$kodeDept},%"]);
                    });
                });
            }
        });

        return $query->pluck('tanggal_libur')
            ->map(function ($item) {
                return date('Y-m-d', strtotime($item));
            })
            ->unique()
            ->values()
            ->toArray();
    }

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
                if (!$request->filled('bulan') && !$request->filled('tahun')) {
                    $showAllHistory = true;
                    $bulan = '';
                    $tahun = '';
                }
            }
        }

        $dataIzinQuery = Izin::query()
            ->with('masterCuti')
            ->where('nik', $nik);

        if (!$showAllHistory) {
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
            $effectiveDates = $this->getEffectiveIzinDates($izin);

            if ($this->isMultiDateStatus($izin->status) && count($effectiveDates) > 0) {
                $izin->total_hari_view = count($effectiveDates);
            } else {
                $tglMulai = new \DateTime($izin->tgl_izin_dari);
                $tglAkhir = new \DateTime($izin->tgl_izin_sampai ?? $izin->tgl_izin_dari);
                $izin->total_hari_view = $tglMulai->diff($tglAkhir)->days + 1;
            }

            $izin->keterangan_view = $this->stripCutiDatesMeta($izin->keterangan);
            $izin->requested_dates_view = [];
            $izin->requested_dates_text = '';
            $izin->requested_dates_compact = '';

            if ($this->isMultiDateStatus($izin->status) && count($effectiveDates) > 0) {
                $izin->requested_dates_view = collect($effectiveDates)
                    ->map(function ($date) {
                        return date('d-m-Y', strtotime($date));
                    })
                    ->values()
                    ->all();

                $izin->requested_dates_text = implode(', ', $izin->requested_dates_view);
                $izin->requested_dates_compact = $this->buildCompactDateSegmentsText($effectiveDates);
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

            if (!$izin) {
                return response()->json(['error' => 'Data tidak ditemukan'], 404);
            }

            $dari = \Carbon\Carbon::parse($izin->tgl_izin_dari);
            $sampai = \Carbon\Carbon::parse($izin->tgl_izin_sampai ?? $izin->tgl_izin_dari);
            
            $requestedDates = [];
            if ($this->isMultiDateStatus($izin->status)) {
                $requestedDates = $this->getEffectiveIzinDates($izin);
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

            $jumlahHari = $this->isMultiDateStatus($izin->status) && !empty($requestedDates)
                ? count($requestedDates)
                : (abs($sampai->diffInDays($dari)) + 1);

            $jenis_badge = '';
            if ($izin->status == 'i') {
                $jenis_badge = '<span class="badge bg-blue-lt">Izin</span>';
            } elseif ($izin->status == 's') {
                $jenis_badge = '<span class="badge bg-pink-lt">Sakit</span>';
            } elseif ($izin->status == 'r') {
                $jenis_badge = '<span class="badge bg-cyan-lt">Roster</span>';
            } elseif (!empty($izin->kode_cuti)) {
                $jenis_badge = '<span class="badge bg-teal-lt">' . ($izin->masterCuti->nama_cuti ?? 'Cuti') . '</span>';
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

            $approvedDates = $this->getApprovedCutiDates(
                $izin->nik,
                $izin->tgl_izin_dari,
                $izin->tgl_izin_sampai,
                $izin->status
            );
            if ($this->isMultiDateStatus($izin->status) && !empty($requestedDates)) {
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
                'keterangan' => $this->stripCutiDatesMeta($izin->keterangan),
                'status_badge' => $status_badge,
                'is_cuti' => $this->isMultiDateStatus($izin->status),
                'status' => $izin->status,
                'approved_dates' => $approvedDates,
                'requested_dates' => $requestedDates,
                'status_approved' => (int) $izin->status_approved,
                'catatan_ditolak' => $izin->catatan_ditolak ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getKodeCutiTahunan()
    {
        $record = MasterCuti::where('nama_cuti', 'like', '%Cuti Tahunan%')->first();

        return $record->kode_cuti ?? 'CTH';
    }

    private function getCutiTakenDays($nik, $tahun, $kode_cuti, $excludeKodeIzin = null)
    {
        $karyawan = Auth::guard('karyawan')->user();
        $kodeCabang = $karyawan->kode_cabang ?? null;
        $kodeDept = $karyawan->kode_dept ?? null;

        $holidayDates = $this->getHariLiburDatesByScope(
            $kodeCabang,
            $kodeDept,
            $tahun . '-01-01',
            $tahun . '-12-31'
        );
        $holidayLookup = array_flip($holidayDates);

        $query = Izin::query()
            ->select('nik', 'status', 'keterangan', 'tgl_izin_dari', 'tgl_izin_sampai')
            ->where('nik', $nik)
            ->where('status', 'c')
            ->where('kode_cuti', $kode_cuti)
            ->where('status_approved', 1)
            ->whereYear('tgl_izin_dari', $tahun);

        if ($excludeKodeIzin) {
            $query->where('kode_izin', '!=', $excludeKodeIzin);
        }

        $rows = $query->get();
        $sumDays = 0;
        $approvedLookup = array_flip(Presensi::where('nik', $nik)
            ->where('status', 'c')
            ->whereYear('tgl_presensi', $tahun)
            ->pluck('tgl_presensi')
            ->map(function ($date) {
                return $date ? $date->format('Y-m-d') : null;
            })
            ->filter(function ($date) {
                return !empty($date);
            })
            ->all());

        foreach ($rows as $r) {
            foreach ($this->getEffectiveIzinDates($r) as $dateStr) {
                if ((int) date('Y', strtotime($dateStr)) !== (int) $tahun) {
                    continue;
                }
                
                $namahari = $this->gethari(date('D', strtotime($dateStr)));
                [$jkObj, $isLiburShift] = $this->resolveJamKerja($nik, $kodeDept, $kodeCabang, $namahari);

                if (isset($holidayLookup[$dateStr]) || $isLiburShift) {
                    continue;
                }
                if (!isset($approvedLookup[$dateStr])) {
                    continue;
                }
                $sumDays++;
            }
        }

        return (int) $sumDays;
    }

    private function generateKodeIzin($lastKode, $format, $padLength)
    {
        if (empty($lastKode) || strpos($lastKode, $format) !== 0) {
            $nextNumber = 1;
        } else {
            $lastNumber = (int) substr($lastKode, -4);
            $nextNumber = $lastNumber + 1;
        }

        return $format . str_pad($nextNumber, $padLength, '0', STR_PAD_LEFT);
    }

    public function createizinabsen()
    {
        return view('karyawan.pengajuanizin.createizinabsen');
    }

    public function createizinsakit()
    {
        return view('karyawan.pengajuanizin.createizinsakit');
    }

    public function createizinterlambat()
    {
        return view('karyawan.pengajuanizin.createizinterlambat');
    }

    public function createizinpulangcepat()
    {
        return view('karyawan.pengajuanizin.createizinpulangcepat');
    }

    public function createizincuti()
    {
        $nik = Auth::guard('karyawan')->user()->nik;
        $mastercuti = MasterCuti::orderBy('kode_cuti')->get();

        $tahun_aktif = date('Y');
        $sisa_cuti_map = [];
        $kodeCutiTahunan = $this->getKodeCutiTahunan();

        foreach ($mastercuti as $mc) {
            $taken = $this->getCutiTakenDays($nik, $tahun_aktif, $mc->kode_cuti);

            if (empty($mc->jml_hari) || (int) $mc->jml_hari <= 0) {
                $sisa_cuti_map[$mc->kode_cuti] = null;
            } else {
                $sisa = (int) $mc->jml_hari - (int) $taken;
                $sisa_cuti_map[$mc->kode_cuti] = max(0, $sisa);
            }
        }

        $sisa_cuti = $sisa_cuti_map[$kodeCutiTahunan] ?? null;

        $viewData = compact('mastercuti', 'sisa_cuti', 'kodeCutiTahunan', 'sisa_cuti_map');
        $viewData['submissionType'] = 'cuti';

        return view('karyawan.pengajuanizin.createizincuti', $viewData);
    }

    public function createizinroster()
    {
        return view('karyawan.pengajuanizin.createizincuti', [
            'submissionType' => 'roster',
            'mastercuti' => collect(),
            'sisa_cuti' => null,
            'kodeCutiTahunan' => null,
            'sisa_cuti_map' => [],
        ]);
    }

    public function storeizinabsen(Request $request)
    {
        $nik = Auth::guard('karyawan')->user()->nik;
        $tgl_izin_dari = $request->dari;
        $tgl_izin_sampai = $request->sampai;
        $status = 'i';
        $keterangan = $request->keterangan;
        $bulan = date('m', strtotime($tgl_izin_dari));
        $tahun = date('Y', strtotime($tgl_izin_dari));
        $thn = substr($tahun, 2, 2);

        $lastizin = Izin::whereMonth('tgl_izin_dari', $bulan)
            ->whereYear('tgl_izin_dari', $tahun)
            ->orderByDesc('kode_izin')
            ->first();

        $lastkodeizin = $lastizin != null ? $lastizin->kode_izin : "";
        $format = "IZ" . $bulan . $thn;
        $kode_izin = $this->generateKodeIzin($lastkodeizin, $format, 4);

        $data = [
            'kode_izin' => $kode_izin,
            'nik' => $nik,
            'tgl_izin_dari' => $tgl_izin_dari,
            'tgl_izin_sampai' => $tgl_izin_sampai,
            'status' => $status,
            'keterangan' => $keterangan
        ];

        try {
            Izin::create($data);
            return redirect('/presensi/izin')->with('success', 'Data Izin Berhasil Disimpan. Kode Izin: ' . $kode_izin);
        } catch (\Exception $e) {
            return redirect('/presensi/izin')->with('error', 'Data Gagal Disimpan. Error: ' . $e->getMessage());
        }
    }

    public function storeizinsakit(Request $request)
    {
        // Validasi request
        $request->validate([
            'dari' => 'required|date',
            'sampai' => 'required|date',
            'keterangan' => 'required|string',
            'sid' => 'nullable|image|max:3072', // 3MB = 3072KB
        ], [
            'sid.max' => 'Ukuran file tidak boleh lebih dari 3MB.',
            'sid.image' => 'File harus berupa gambar.',
        ]);

        $nik = Auth::guard('karyawan')->user()->nik;
        $tgl_izin_dari = $request->dari;
        $tgl_izin_sampai = $request->sampai;
        $status = 's';
        $keterangan = $request->keterangan;
        $bulan = date('m', strtotime($tgl_izin_dari));
        $tahun = date('Y', strtotime($tgl_izin_dari));
        $thn = substr($tahun, 2, 2);

        $lastizin = Izin::whereMonth('tgl_izin_dari', $bulan)
            ->whereYear('tgl_izin_dari', $tahun)
            ->orderByDesc('kode_izin')
            ->first();

        $lastkodeizin = $lastizin != null ? $lastizin->kode_izin : "";
        $format = "IZ" . $bulan . $thn;
        $kode_izin = $this->generateKodeIzin($lastkodeizin, $format, 4);

        $data = [
            'kode_izin' => $kode_izin,
            'nik' => $nik,
            'tgl_izin_dari' => $tgl_izin_dari,
            'tgl_izin_sampai' => $tgl_izin_sampai,
            'status' => $status,
            'keterangan' => $keterangan,
        ];

        try {
            $simpan = Izin::create($data);

            if ($simpan && $request->hasFile('sid')) {
                $kode_izin_yang_baru_disimpan = $kode_izin;

                $sid_file_extension = $request->file('sid')->getClientOriginalExtension();
                $sid_file_name = $kode_izin_yang_baru_disimpan . "." . $sid_file_extension;

                $folderPath = "uploads/sid";
                $request->file('sid')->storeAs($folderPath, $sid_file_name, 'public');

                if (Schema::hasColumn('izin', 'doc_sid')) {
                    Izin::where('kode_izin', $kode_izin_yang_baru_disimpan)->update(['doc_sid' => $sid_file_name]);
                }
            }

            return redirect('/presensi/izin')->with('success', 'Data Izin Sakit Berhasil Disimpan. Kode Izin: ' . $kode_izin);
        } catch (\Exception $e) {
            return redirect('/presensi/izin')->with('error', 'Data Izin Sakit Gagal Disimpan. Error: ' . $e->getMessage());
        }
    }

    public function storeizinterlambat(Request $request)
    {
        $nik = Auth::guard('karyawan')->user()->nik;
        // Force terlambat izin to today's date (single day)
        $tgl_izin_dari = date('Y-m-d');
        $tgl_izin_sampai = date('Y-m-d');

        $sudahAjukanHariIni = Izin::where('nik', $nik)
            ->where('status', 't')
            ->whereDate('tgl_izin_dari', $tgl_izin_dari)
            ->exists();

        if ($sudahAjukanHariIni) {
            return redirect('/presensi/izin')->with('error', 'Anda sudah mengajukan izin terlambat hari ini.');
        }

        $status = 't';
        $keterangan = $request->keterangan;
        $bulan = date('m', strtotime($tgl_izin_dari));
        $tahun = date('Y', strtotime($tgl_izin_dari));
        $thn = substr($tahun, 2, 2);

        $lastizin = Izin::whereMonth('tgl_izin_dari', $bulan)
            ->whereYear('tgl_izin_dari', $tahun)
            ->orderByDesc('kode_izin')
            ->first();

        $lastkodeizin = $lastizin != null ? $lastizin->kode_izin : "";
        $format = "IZ" . $bulan . $thn;
        $kode_izin = $this->generateKodeIzin($lastkodeizin, $format, 4);

        $data = [
            'kode_izin' => $kode_izin,
            'nik' => $nik,
            'tgl_izin_dari' => $tgl_izin_dari,
            'tgl_izin_sampai' => $tgl_izin_sampai,
            'status' => $status,
            'keterangan' => $keterangan
        ];

        try {
            Izin::create($data);
            return redirect('/presensi/izin')->with('success', 'Pengajuan Izin Terlambat Berhasil Disimpan. Kode Izin: ' . $kode_izin);
        } catch (\Exception $e) {
            return redirect('/presensi/izin')->with('error', 'Data Gagal Disimpan. Error: ' . $e->getMessage());
        }
    }

    public function storeizinpulangcepat(Request $request)
    {
        $nik = Auth::guard('karyawan')->user()->nik;
        $tanggal = date('Y-m-d');

        $presensiHariIni = Presensi::where('nik', $nik)
            ->whereDate('tgl_presensi', $tanggal)
            ->first();

        if (!$presensiHariIni || empty($presensiHariIni->jam_in) || $presensiHariIni->jam_in == '00:00:00') {
            return redirect('/presensi/izin')->with('error', 'Pengajuan pulang cepat hanya bisa dilakukan setelah absen masuk.');
        }

        if (!empty($presensiHariIni->jam_out) && $presensiHariIni->jam_out != '00:00:00') {
            return redirect('/presensi/izin')->with('error', 'Anda sudah absen pulang hari ini.');
        }

        $cekDuplikat = Izin::where('nik', $nik)
            ->whereDate('tgl_izin_dari', $tanggal)
            ->where('status', 'p')
            ->whereIn('status_approved', [0, 1])
            ->exists();

        if ($cekDuplikat) {
            return redirect('/presensi/izin')->with('error', 'Pengajuan pulang cepat untuk hari ini sudah ada.');
        }

        // Izin terlambat (t) boleh berdampingan dengan pulang cepat (p) pada tanggal yang sama.
        $cekIzinLain = Izin::where('nik', $nik)
            ->whereNotIn('status', ['p', 't'])
            ->whereIn('status_approved', [0, 1])
            ->whereDate('tgl_izin_dari', '<=', $tanggal)
            ->whereDate('tgl_izin_sampai', '>=', $tanggal)
            ->exists();

        if ($cekIzinLain) {
            return redirect('/presensi/izin')->with('error', 'Tidak dapat mengajukan pulang cepat karena ada pengajuan izin lain pada tanggal yang sama.');
        }

        $keterangan = $request->keterangan;
        $bulan = date('m', strtotime($tanggal));
        $tahun = date('Y', strtotime($tanggal));
        $thn = substr($tahun, 2, 2);

        $lastizin = Izin::whereMonth('tgl_izin_dari', $bulan)
            ->whereYear('tgl_izin_dari', $tahun)
            ->orderByDesc('kode_izin')
            ->first();

        $lastkodeizin = $lastizin != null ? $lastizin->kode_izin : "";
        $format = "IZ" . $bulan . $thn;
        $kode_izin = $this->generateKodeIzin($lastkodeizin, $format, 4);

        $data = [
            'kode_izin' => $kode_izin,
            'nik' => $nik,
            'tgl_izin_dari' => $tanggal,
            'tgl_izin_sampai' => $tanggal,
            'status' => 'p',
            'keterangan' => $keterangan
        ];

        try {
            Izin::create($data);
            return redirect('/presensi/izin')->with('success', 'Pengajuan Pulang Cepat Berhasil Disimpan. Kode Izin: ' . $kode_izin);
        } catch (\Exception $e) {
            return redirect('/presensi/izin')->with('error', 'Data Gagal Disimpan. Error: ' . $e->getMessage());
        }
    }

    public function storeizincuti(Request $request)
    {
        $karyawan = Auth::guard('karyawan')->user();
        $nik = $karyawan->nik;
        $kode_cuti = $request->kode_cuti;
        $keterangan = trim((string) $request->keterangan);

        // Cek Surat Peringatan Aktif
        $hasActiveSP = SuratPeringatan::where('nik', $nik)
            ->whereDate('expires_at', '>', Carbon::today())
            ->exists();

        if ($hasActiveSP) {
            throw ValidationException::withMessages([
                'kode_cuti' => 'Maaf, Anda memiliki Surat Peringatan (SP) yang masih aktif sehingga tidak dapat mengajukan cuti.'
            ]);
        }

        $selectedDates = collect(explode(',', (string) $request->selected_dates))
            ->map(function ($date) {
                return trim($date);
            })
            ->filter(function ($date) {
                return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
            })
            ->unique()
            ->sort()
            ->values();

        if ($selectedDates->isEmpty()) {
            throw ValidationException::withMessages([
                'selected_dates' => 'Pilih minimal satu tanggal cuti.'
            ]);
        }

        $jml_hari = $selectedDates->count();

        $holidayDates = $this->getHariLiburDatesByScope(
            $karyawan->kode_cabang ?? null,
            $karyawan->kode_dept ?? null,
            $selectedDates->first(),
            $selectedDates->last()
        );

        $selectedHolidayDates = $selectedDates->intersect($holidayDates)->values();
        
        // Cek Libur Shift Khusus menggunakan resolveJamKerja (Filter "Terima Beres")
        $finalSelectedDates = collect();
        foreach ($selectedDates as $sd) {
            $namahari = $this->gethari(date('D', strtotime($sd)));
            [$jkObj, $isLiburShift] = $this->resolveJamKerja($nik, $karyawan->kode_dept, $karyawan->kode_cabang, $namahari);
            if (!$isLiburShift && !$selectedHolidayDates->contains($sd)) {
                $finalSelectedDates->push($sd);
            }
        }

        if ($finalSelectedDates->isEmpty()) {
            throw ValidationException::withMessages([
                'selected_dates' => 'Seluruh tanggal yang dipilih bertepatan dengan libur / shift libur.'
            ]);
        }

        $jml_hari = $finalSelectedDates->count();

        $existingPresensi = Presensi::where('nik', $nik)
            ->whereIn('tgl_presensi', $finalSelectedDates->all())
            ->whereIn('status', ['h', 'i', 's', 'c', 'r'])
            ->exists();

        $existingIzinOverlap = $this->hasIzinDateConflict($nik, $finalSelectedDates);

        if ($existingPresensi || $existingIzinOverlap) {
            throw ValidationException::withMessages([
                'selected_dates' => 'Sebagian tanggal yang diajukan sudah memiliki presensi / izin lain.'
            ]);
        }

        $masterCutiRec = MasterCuti::find($kode_cuti);
        if ($masterCutiRec && $masterCutiRec->jml_hari && (int) $masterCutiRec->jml_hari > 0) {
            $jatah_cuti = (int) $masterCutiRec->jml_hari;
            $cuti_diambil = $this->getCutiTakenDays($nik, date('Y'), $kode_cuti);
            $sisa_cuti = $jatah_cuti - $cuti_diambil;

            if ($jml_hari > $sisa_cuti) {
                throw ValidationException::withMessages([
                    'jmlhari' => "Sisa jatah cuti untuk jenis {$masterCutiRec->nama_cuti} Anda ({$sisa_cuti} hari) tidak mencukupi untuk {$jml_hari} hari efektif yang diajukan."
                ]);
            }
        }

        try {
            $bulan = date('m', strtotime($selectedDates->first()));
            $tahun = date('Y', strtotime($selectedDates->first()));
            $thn = substr($tahun, 2, 2);

            $lastizin = Izin::whereMonth('tgl_izin_dari', $bulan)
                ->whereYear('tgl_izin_dari', $tahun)
                ->orderByDesc('kode_izin')
                ->first();

            $lastkodeizin = $lastizin != null ? $lastizin->kode_izin : "";
            $format = "IZ" . $bulan . $thn;
            $kode_izin = $this->generateKodeIzin($lastkodeizin, $format, 4);

            Izin::create([
                'kode_izin' => $kode_izin,
                'nik' => $nik,
                'tgl_izin_dari' => $finalSelectedDates->first(),
                'tgl_izin_sampai' => $finalSelectedDates->last(),
                'status' => 'c',
                'kode_cuti' => $kode_cuti,
                'keterangan' => $this->appendCutiDatesMeta($keterangan, $finalSelectedDates),
            ]);

            return redirect('/presensi/izin')->with('success', 'Pengajuan cuti berhasil disimpan.');
        } catch (ValidationException $e) {
            return Redirect::back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect('/presensi/izin')->with('error', 'Data Gagal Disimpan. Error: ' . $e->getMessage());
        }
    }

    public function storeizinroster(Request $request)
    {
        $karyawan = Auth::guard('karyawan')->user();
        $nik = $karyawan->nik;
        $keterangan = trim((string) $request->keterangan);

        $selectedDates = collect(explode(',', (string) $request->selected_dates))
            ->map(function ($date) {
                return trim($date);
            })
            ->filter(function ($date) {
                return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
            })
            ->unique()
            ->sort()
            ->values();

        if ($selectedDates->isEmpty()) {
            throw ValidationException::withMessages([
                'selected_dates' => 'Pilih minimal satu tanggal roster.'
            ]);
        }

        $holidayDates = $this->getHariLiburDatesByScope(
            $karyawan->kode_cabang ?? null,
            $karyawan->kode_dept ?? null,
            $selectedDates->first(),
            $selectedDates->last()
        );

        $finalSelectedDates = collect();
        foreach ($selectedDates as $selectedDate) {
            $namaHari = $this->gethari(date('D', strtotime($selectedDate)));
            [$jamKerja, $isLiburShift] = $this->resolveJamKerja($nik, $karyawan->kode_dept, $karyawan->kode_cabang, $namaHari);

            if (!$isLiburShift && !in_array($selectedDate, $holidayDates, true)) {
                $finalSelectedDates->push($selectedDate);
            }
        }

        if ($finalSelectedDates->isEmpty()) {
            throw ValidationException::withMessages([
                'selected_dates' => 'Seluruh tanggal yang dipilih bertepatan dengan libur / shift libur.'
            ]);
        }

        $existingPresensi = Presensi::where('nik', $nik)
            ->whereIn('tgl_presensi', $finalSelectedDates->all())
            ->whereIn('status', ['h', 'i', 's', 'c', 'r'])
            ->exists();

        $existingIzinOverlap = $this->hasIzinDateConflict($nik, $finalSelectedDates);

        if ($existingPresensi || $existingIzinOverlap) {
            throw ValidationException::withMessages([
                'selected_dates' => 'Sebagian tanggal yang diajukan sudah memiliki presensi / izin lain.'
            ]);
        }

        try {
            $bulan = date('m', strtotime($finalSelectedDates->first()));
            $tahun = date('Y', strtotime($finalSelectedDates->first()));
            $thn = substr($tahun, 2, 2);

            $lastizin = Izin::whereMonth('tgl_izin_dari', $bulan)
                ->whereYear('tgl_izin_dari', $tahun)
                ->orderByDesc('kode_izin')
                ->first();

            $lastkodeizin = $lastizin != null ? $lastizin->kode_izin : "";
            $format = "IZ" . $bulan . $thn;
            $kode_izin = $this->generateKodeIzin($lastkodeizin, $format, 4);

            Izin::create([
                'kode_izin' => $kode_izin,
                'nik' => $nik,
                'tgl_izin_dari' => $finalSelectedDates->first(),
                'tgl_izin_sampai' => $finalSelectedDates->last(),
                'status' => 'r',
                'kode_cuti' => null,
                'keterangan' => $this->appendCutiDatesMeta($keterangan, $finalSelectedDates),
            ]);

            return redirect('/presensi/izin')->with('success', 'Pengajuan roster berhasil disimpan.');
        } catch (ValidationException $e) {
            return Redirect::back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect('/presensi/izin')->with('error', 'Data Gagal Disimpan. Error: ' . $e->getMessage());
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
            $izinDates = array_merge($izinDates, $this->getEffectiveIzinDates($record));
        }

        $today = date('Y-m-d');
        $oneYearAhead = date('Y-m-d', strtotime('+1 year'));

        $holidayDates = $this->getHariLiburDatesByScope(
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
                $d = $dt->format("Y-m-d");
                $namahari = $this->gethari($dt->format('D'));
                [$jkObj, $isLiburShift] = $this->resolveJamKerja($nik, $karyawan->kode_dept, $karyawan->kode_cabang, $namahari);
                
                if ($isLiburShift || (strtolower($namahari) === 'minggu' && !$jkObj)) {
                    $shiftLiburDates[] = $d;
                }
            }
        } catch (\Exception $e) { }

        $blacklistDates = array_unique(array_merge($presensiDates, $izinDates, $holidayDates, $shiftLiburDates));

        if ($request->boolean('include_holidays')) {
            return response()->json([
                'blacklist_dates' => array_values($blacklistDates),
                'holiday_dates' => array_values($holidayDates)
            ]);
        }

        return response()->json(array_values($blacklistDates));
    }

    public function edit($kode_izin)
    {
        $dataizin = Izin::where('kode_izin', $kode_izin)->first();

        if (!$dataizin) {
            return Redirect::back()->with('error', 'Data Izin tidak ditemukan.');
        }

        if ($dataizin->status_approved != 0) {
            return Redirect::back()->with('error', 'Pengajuan ini sudah diverifikasi dan tidak bisa diubah.');
        }

        if ($dataizin->status == 'i') {
            return $this->editizinabsen($kode_izin);
        } elseif ($dataizin->status == 't') {
            return $this->editizinterlambat($kode_izin);
        } elseif ($dataizin->status == 'p') {
            return $this->editizinpulangcepat($kode_izin);
        } elseif ($dataizin->status == 's') {
            return $this->editizinsakit($kode_izin);
        } elseif ($dataizin->status == 'c') {
            return $this->editizincuti($kode_izin);
        } elseif ($dataizin->status == 'r') {
            return $this->editizinroster($kode_izin);
        } else {
            return Redirect::back()->with('error', 'Jenis pengajuan izin tidak valid.');
        }
    }

    public function editizinabsen($kode_izin)
    {
        $dataizin = Izin::where('kode_izin', $kode_izin)->first();
        if (!$dataizin || $dataizin->status != 'i') {
            return Redirect::back()->with('error', 'Data Izin Absen tidak valid.');
        }
        return view('karyawan.pengajuanizin.editizinabsen', compact('dataizin'));
    }

    public function editizinterlambat($kode_izin)
    {
        $dataizin = Izin::where('kode_izin', $kode_izin)->first();
        if (!$dataizin || $dataizin->status != 't') {
            return Redirect::back()->with('error', 'Data Izin Terlambat tidak valid.');
        }

        if ($dataizin->status_approved != 0) {
            return Redirect::back()->with('error', 'Pengajuan ini sudah diverifikasi dan tidak bisa diubah.');
        }

        return view('karyawan.pengajuanizin.editizinterlambat', compact('dataizin'));
    }

    public function updateizinabsen($kode_izin, Request $request)
    {
        $tgl_izin_dari = $request->dari;
        $tgl_izin_sampai = $request->sampai;
        $keterangan = $request->keterangan;

        $data_update = [
            'tgl_izin_dari' => $tgl_izin_dari,
            'tgl_izin_sampai' => $tgl_izin_sampai,
            'keterangan' => $keterangan,
        ];

        try {
            Izin::where('kode_izin', $kode_izin)->update($data_update);
            return redirect('/presensi/izin')->with('success', 'Data Izin Absen Berhasil Diupdate.');
        } catch (\Exception $e) {
            return redirect('/presensi/izin')->with('error', 'Data Izin Absen Gagal Diupdate. Error: ' . $e->getMessage());
        }
    }

    public function updateizinterlambat($kode_izin, Request $request)
    {
        // Ensure terlambat izin remains a single-day request and use server date
        $tgl_izin_dari = date('Y-m-d');
        // If client sent a 'dari' we ignore it to enforce same-day rule; keep sampai equal to dari
        $tgl_izin_sampai = date('Y-m-d');
        $keterangan = $request->keterangan;

        $data_update = [
            'tgl_izin_dari' => $tgl_izin_dari,
            'tgl_izin_sampai' => $tgl_izin_sampai,
            'keterangan' => $keterangan,
        ];

        try {
            Izin::where('kode_izin', $kode_izin)->update($data_update);
            return redirect('/pengajuanizin/index')->with('success', 'Data Izin Terlambat Berhasil Diupdate.');
        } catch (\Exception $e) {
            return redirect('/pengajuanizin/index')->with('error', 'Data Izin Terlambat Gagal Diupdate. Error: ' . $e->getMessage());
        }
    }

    public function editizinpulangcepat($kode_izin)
    {
        $dataizin = Izin::where('kode_izin', $kode_izin)->first();
        if (!$dataizin || $dataizin->status != 'p') {
            return Redirect::back()->with('error', 'Data Izin Pulang Cepat tidak valid.');
        }

        if ($dataizin->status_approved != 0) {
            return Redirect::back()->with('error', 'Pengajuan ini sudah diverifikasi dan tidak bisa diubah.');
        }

        return view('karyawan.pengajuanizin.editizinpulangcepat', compact('dataizin'));
    }

    public function updateizinpulangcepat($kode_izin, Request $request)
    {
        $keterangan = $request->keterangan;

        $data_update = [
            'tgl_izin_dari' => date('Y-m-d'),
            'tgl_izin_sampai' => date('Y-m-d'),
            'keterangan' => $keterangan,
        ];

        try {
            Izin::where('kode_izin', $kode_izin)->update($data_update);
            return redirect('/pengajuanizin/index')->with('success', 'Data Izin Pulang Cepat Berhasil Diupdate.');
        } catch (\Exception $e) {
            return redirect('/pengajuanizin/index')->with('error', 'Data Izin Pulang Cepat Gagal Diupdate. Error: ' . $e->getMessage());
        }
    }

    public function editizinsakit($kode_izin)
    {
        $dataizin = Izin::where('kode_izin', $kode_izin)->first();
        if (!$dataizin || $dataizin->status != 's') {
            return Redirect::back()->with('error', 'Data Izin Sakit tidak valid.');
        }

        if ($dataizin->status_approved != 0) {
            return Redirect::back()->with('error', 'Pengajuan ini sudah diverifikasi dan tidak bisa diubah.');
        }

        return view('karyawan.pengajuanizin.editizinsakit', compact('dataizin'));
    }

    public function updateizinsakit($kode_izin, Request $request)
    {
        $tgl_izin_dari = $request->dari;
        $tgl_izin_sampai = $request->sampai;
        $keterangan = $request->keterangan;
        $old_doc_sid = Izin::where('kode_izin', $kode_izin)->value('doc_sid');

        $data_update = [
            'tgl_izin_dari' => $tgl_izin_dari,
            'tgl_izin_sampai' => $tgl_izin_sampai,
            'keterangan' => $keterangan,
        ];

        try {
            if ($request->hasFile('sid')) {
                // Delete old file if it exists
                $folderPath = "uploads/sid";
                if (!empty($old_doc_sid) && $old_doc_sid !== '-') {
                    Storage::disk('public')->delete($folderPath . '/' . $old_doc_sid);
                }

                $sid_file_extension = $request->file('sid')->getClientOriginalExtension();
                $sid_file_name = $kode_izin . "." . $sid_file_extension;

                $request->file('sid')->storeAs($folderPath, $sid_file_name, 'public');

                // Update doc_sid only when the column exists
                if (Schema::hasColumn('izin', 'doc_sid')) {
                    $data_update['doc_sid'] = $sid_file_name;
                }
            }

            Izin::where('kode_izin', $kode_izin)->update($data_update);
            return redirect('/presensi/izin')->with('success', 'Data Izin Sakit Berhasil Diupdate.');
        } catch (\Exception $e) {
            return redirect('/presensi/izin')->with('error', 'Data Izin Sakit Gagal Diupdate. Error: ' . $e->getMessage());
        }
    }

    public function editizincuti($kode_izin)
    {
        $nik = Auth::guard('karyawan')->user()->nik;
        $tahun_aktif = date('Y');

        $dataizin = Izin::with('masterCuti')
            ->where('kode_izin', $kode_izin)
            ->first();

        if (!$dataizin || $dataizin->status != 'c') {
            return Redirect::back()->with('error', 'Data Izin Cuti tidak valid.');
        }

        if ($dataizin->status_approved != 0) {
            return Redirect::back()->with('error', 'Pengajuan ini sudah diverifikasi dan tidak bisa diubah.');
        }

        $mastercuti = MasterCuti::orderBy('kode_cuti')->get();
        $sisa_cuti_map = [];
        $kodeCutiTahunan = $this->getKodeCutiTahunan();

        foreach ($mastercuti as $mc) {
            $taken = $this->getCutiTakenDays($nik, $tahun_aktif, $mc->kode_cuti, $kode_izin);

            if (empty($mc->jml_hari) || (int) $mc->jml_hari <= 0) {
                $sisa_cuti_map[$mc->kode_cuti] = null;
            } else {
                $sisa = (int) ($mc->jml_hari ?? 0) - (int) $taken;
                $sisa_cuti_map[$mc->kode_cuti] = max(0, $sisa);
            }
        }

        $sisa_cuti = $sisa_cuti_map[$kodeCutiTahunan] ?? null;

        $initialSelectedDates = $this->getEffectiveIzinDates($dataizin);
        $keterangan_plain = $this->stripCutiDatesMeta($dataizin->keterangan);

        return view('karyawan.pengajuanizin.editizincuti', compact('dataizin', 'mastercuti', 'sisa_cuti', 'kodeCutiTahunan', 'sisa_cuti_map', 'kode_izin', 'initialSelectedDates', 'keterangan_plain') + [
            'submissionType' => 'cuti',
        ]);
    }

    public function editizinroster($kode_izin)
    {
        $dataizin = Izin::where('kode_izin', $kode_izin)->first();

        if (!$dataizin || $dataizin->status != 'r') {
            return Redirect::back()->with('error', 'Data Pengajuan Roster tidak valid.');
        }

        if ($dataizin->status_approved != 0) {
            return Redirect::back()->with('error', 'Pengajuan ini sudah diverifikasi dan tidak bisa diubah.');
        }

        $initialSelectedDates = $this->getEffectiveIzinDates($dataizin);
        $keterangan_plain = $this->stripCutiDatesMeta($dataizin->keterangan);

        return view('karyawan.pengajuanizin.editizincuti', [
            'dataizin' => $dataizin,
            'mastercuti' => collect(),
            'sisa_cuti' => null,
            'kodeCutiTahunan' => null,
            'sisa_cuti_map' => [],
            'kode_izin' => $kode_izin,
            'initialSelectedDates' => $initialSelectedDates,
            'keterangan_plain' => $keterangan_plain,
            'submissionType' => 'roster',
        ]);
    }

    public function updateizincuti($kode_izin, Request $request)
    {
        $karyawan = Auth::guard('karyawan')->user();
        $nik = $karyawan->nik;
        $kode_cuti = $request->kode_cuti;
        $keterangan = trim((string) $request->keterangan);

        // Cek Surat Peringatan Aktif
        $hasActiveSP = SuratPeringatan::where('nik', $nik)
            ->whereDate('expires_at', '>', Carbon::today())
            ->exists();

        if ($hasActiveSP) {
            throw ValidationException::withMessages([
                'kode_cuti' => 'Maaf, Anda memiliki Surat Peringatan (SP) yang masih aktif sehingga tidak dapat mengupdate pengajuan cuti.'
            ]);
        }

        $selectedDates = collect(explode(',', (string) $request->selected_dates))
            ->map(function ($date) {
                return trim($date);
            })
            ->filter(function ($date) {
                return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
            })
            ->unique()
            ->sort()
            ->values();

        if ($selectedDates->isEmpty()) {
            throw ValidationException::withMessages([
                'selected_dates' => 'Pilih minimal satu tanggal cuti.'
            ]);
        }

        $jml_hari = $selectedDates->count();

        $holidayDates = $this->getHariLiburDatesByScope(
            $karyawan->kode_cabang ?? null,
            $karyawan->kode_dept ?? null,
            $selectedDates->first(),
            $selectedDates->last()
        );

        $selectedHolidayDates = $selectedDates->intersect($holidayDates)->values();
        if ($selectedHolidayDates->isNotEmpty()) {
            throw ValidationException::withMessages([
                'selected_dates' => 'Tanggal ' . $selectedHolidayDates->join(', ') . ' adalah hari libur dan tidak dapat diajukan cuti.'
            ]);
        }

        $existingPresensi = Presensi::where('nik', $nik)
            ->whereIn('tgl_presensi', $selectedDates->all())
            ->whereIn('status', ['h', 'i', 's', 'c', 'r'])
            ->exists();

        $existingIzinOverlap = $this->hasIzinDateConflict($nik, $selectedDates, $kode_izin);

        if ($existingPresensi || $existingIzinOverlap) {
            throw ValidationException::withMessages([
                'selected_dates' => 'Sebagian tanggal yang dipilih bentrok dengan presensi/pengajuan izin lain.'
            ]);
        }

        $masterCutiRec = MasterCuti::find($kode_cuti);
        if ($masterCutiRec && $masterCutiRec->jml_hari && (int) $masterCutiRec->jml_hari > 0) {
            $jatah_cuti = (int) $masterCutiRec->jml_hari;
            $cuti_diambil = $this->getCutiTakenDays($nik, date('Y'), $kode_cuti, $kode_izin);
            $sisa_cuti = $jatah_cuti - $cuti_diambil;

            if ($jml_hari > $sisa_cuti) {
                throw ValidationException::withMessages([
                    'jmlhari' => "Sisa jatah cuti untuk jenis {$masterCutiRec->nama_cuti} Anda ({$sisa_cuti} hari) tidak mencukupi untuk {$jml_hari} hari yang diajukan."
                ]);
            }
        }

        try {
            Izin::where('kode_izin', $kode_izin)->update([
                'tgl_izin_dari' => $selectedDates->first(),
                'tgl_izin_sampai' => $selectedDates->last(),
                'status' => 'c',
                'kode_cuti' => $kode_cuti,
                'keterangan' => $this->appendCutiDatesMeta($keterangan, $selectedDates),
            ]);

            return redirect('/presensi/izin')->with('success', 'Data Pengajuan Cuti Berhasil Diupdate.');
        } catch (ValidationException $e) {
            return Redirect::back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect('/presensi/izin')->with('error', 'Data Pengajuan Cuti Gagal Diupdate. Error: ' . $e->getMessage());
        }
    }

    public function updateizinroster($kode_izin, Request $request)
    {
        $karyawan = Auth::guard('karyawan')->user();
        $nik = $karyawan->nik;
        $keterangan = trim((string) $request->keterangan);

        $selectedDates = collect(explode(',', (string) $request->selected_dates))
            ->map(function ($date) {
                return trim($date);
            })
            ->filter(function ($date) {
                return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
            })
            ->unique()
            ->sort()
            ->values();

        if ($selectedDates->isEmpty()) {
            throw ValidationException::withMessages([
                'selected_dates' => 'Pilih minimal satu tanggal roster.'
            ]);
        }

        $holidayDates = $this->getHariLiburDatesByScope(
            $karyawan->kode_cabang ?? null,
            $karyawan->kode_dept ?? null,
            $selectedDates->first(),
            $selectedDates->last()
        );

        $finalSelectedDates = collect();
        foreach ($selectedDates as $selectedDate) {
            $namaHari = $this->gethari(date('D', strtotime($selectedDate)));
            [$jamKerja, $isLiburShift] = $this->resolveJamKerja($nik, $karyawan->kode_dept, $karyawan->kode_cabang, $namaHari);

            if (!$isLiburShift && !in_array($selectedDate, $holidayDates, true)) {
                $finalSelectedDates->push($selectedDate);
            }
        }

        if ($finalSelectedDates->isEmpty()) {
            throw ValidationException::withMessages([
                'selected_dates' => 'Seluruh tanggal yang dipilih bertepatan dengan libur / shift libur.'
            ]);
        }

        $existingPresensi = Presensi::where('nik', $nik)
            ->whereIn('tgl_presensi', $finalSelectedDates->all())
            ->whereIn('status', ['h', 'i', 's', 'c', 'r'])
            ->exists();

        $existingIzinOverlap = $this->hasIzinDateConflict($nik, $finalSelectedDates, $kode_izin);

        if ($existingPresensi || $existingIzinOverlap) {
            throw ValidationException::withMessages([
                'selected_dates' => 'Sebagian tanggal yang dipilih bentrok dengan presensi/pengajuan izin lain.'
            ]);
        }

        try {
            Izin::where('kode_izin', $kode_izin)->update([
                'tgl_izin_dari' => $finalSelectedDates->first(),
                'tgl_izin_sampai' => $finalSelectedDates->last(),
                'status' => 'r',
                'kode_cuti' => null,
                'keterangan' => $this->appendCutiDatesMeta($keterangan, $finalSelectedDates),
            ]);

            return redirect('/presensi/izin')->with('success', 'Data Pengajuan Roster Berhasil Diupdate.');
        } catch (ValidationException $e) {
            return Redirect::back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect('/presensi/izin')->with('error', 'Data Pengajuan Roster Gagal Diupdate. Error: ' . $e->getMessage());
        }
    }

    public function destroy($kode_izin)
    {
        $nik = Auth::guard('karyawan')->user()->nik;

        $izin = Izin::where('kode_izin', $kode_izin)
            ->where('nik', $nik)
            ->first();

        if (!$izin) {
            return Redirect::back()->with('error', 'Data pengajuan tidak ditemukan atau bukan milik Anda.');
        }

        if ($izin->status_approved != 0) {
            return Redirect::back()->with('error', 'Pengajuan yang sudah diverifikasi tidak dapat dihapus.');
        }

        try {
            // Delete associated document file if exists
            if (Schema::hasColumn('izin', 'doc_sid') && !empty($izin->doc_sid) && $izin->doc_sid !== '-') {
                $folderPath = "uploads/sid";
                Storage::disk('public')->delete($folderPath . '/' . $izin->doc_sid);
            }

            $izin->delete();

            return redirect('/presensi/izin')->with('success', 'Data pengajuan berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect('/presensi/izin')->with('error', 'Gagal menghapus data pengajuan. Error: ' . $e->getMessage());
        }
    }

    private function gethari($hari)
    {
        switch ($hari) {
            case 'Sun': return "Minggu";
            case 'Mon': return "Senin";
            case 'Tue': return "Selasa";
            case 'Wed': return "Rabu";
            case 'Thu': return "Kamis";
            case 'Fri': return "Jumat";
            case 'Sat': return "Sabtu";
            default: return "Tidak diketahui";
        }
    }

    private function resolveJamKerja(string $nik, ?string $kodeDept, ?string $kodeCabang, string $hari): array
    {
        $hariNormal = strtolower(trim($hari));

        $setJamKerja = Setjamkerja::with('jamKerja')
            ->where('nik', $nik)
            ->where(DB::raw('LOWER(hari)'), $hariNormal)
            ->first();

        if ($setJamKerja) {
            $isLibur = is_null($setJamKerja->kode_jam_kerja) || $setJamKerja->kode_jam_kerja === 'LIBUR';
            return [$setJamKerja->jamKerja, $isLibur];
        }

        if ($kodeDept && $kodeCabang) {
            $setJamKerjaDept = KonfigurasiJkDeptDetail::with(['jamKerja', 'konfigurasi'])
                ->where(DB::raw('LOWER(hari)'), $hariNormal)
                ->whereHas('konfigurasi', function ($query) use ($kodeDept, $kodeCabang) {
                    $query->where('kode_dept', $kodeDept)->where('kode_cabang', $kodeCabang);
                })->first();

            if ($setJamKerjaDept) {
                $isLibur = is_null($setJamKerjaDept->kode_jam_kerja) || $setJamKerjaDept->kode_jam_kerja === 'LIBUR';
                return [$setJamKerjaDept->jamKerja, $isLibur];
            }
        }

        return [null, false];
    }
}