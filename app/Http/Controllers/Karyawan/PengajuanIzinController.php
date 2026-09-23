<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
use App\Models\Izin;
use App\Models\MasterCuti;
use App\Models\Presensi;
use App\Models\SuratPeringatan;
use App\Services\IzinService;
use App\Services\JadwalKerjaService;
use App\Support\CutiDatesMeta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PengajuanIzinController extends Controller
{
    public function __construct(
        private JadwalKerjaService $jadwalKerja,
        private IzinService $izin,
    ) {}

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
                $jenis_badge = '<span class="badge bg-teal-lt">'.($izin->masterCuti->nama_cuti ?? 'Cuti').'</span>';
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
        $kodeCutiTahunan = $this->izin->kodeCutiTahunan();

        foreach ($mastercuti as $mc) {
            $taken = $this->izin->cutiTakenDays(Auth::guard('karyawan')->user(), $tahun_aktif, $mc->kode_cuti);

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
        $request->validate([
            'dari' => 'required|date',
            'sampai' => 'required|date|after_or_equal:dari',
            'keterangan' => 'required|string',
        ]);

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

        $lastkodeizin = $lastizin != null ? $lastizin->kode_izin : '';
        $format = 'IZ'.$bulan.$thn;
        $kode_izin = $this->izin->generateKodeIzin($lastkodeizin, $format, 4);

        $data = [
            'kode_izin' => $kode_izin,
            'nik' => $nik,
            'tgl_izin_dari' => $tgl_izin_dari,
            'tgl_izin_sampai' => $tgl_izin_sampai,
            'status' => $status,
            'keterangan' => $keterangan,
        ];

        try {
            Izin::create($data);

            return redirect('/presensi/izin')->with('success', 'Data Izin Berhasil Disimpan. Kode Izin: '.$kode_izin);
        } catch (\Exception $e) {
            return redirect('/presensi/izin')->with('error', $this->failMessage('Data Gagal Disimpan.', $e));
        }
    }

    public function storeizinsakit(Request $request)
    {
        // Validasi request
        $request->validate([
            'dari' => 'required|date',
            'sampai' => 'required|date',
            'keterangan' => 'required|string',
            'sid' => 'nullable|image|mimes:jpg,jpeg,png|max:3072', // 3MB = 3072KB
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

        $lastkodeizin = $lastizin != null ? $lastizin->kode_izin : '';
        $format = 'IZ'.$bulan.$thn;
        $kode_izin = $this->izin->generateKodeIzin($lastkodeizin, $format, 4);

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

                $sid_file_name = $this->storeSuratSakit($request->file('sid'), $kode_izin_yang_baru_disimpan);

                if (Schema::hasColumn('izin', 'doc_sid')) {
                    Izin::where('kode_izin', $kode_izin_yang_baru_disimpan)->update(['doc_sid' => $sid_file_name]);
                }
            }

            return redirect('/presensi/izin')->with('success', 'Data Izin Sakit Berhasil Disimpan. Kode Izin: '.$kode_izin);
        } catch (\Exception $e) {
            return redirect('/presensi/izin')->with('error', $this->failMessage('Data Izin Sakit Gagal Disimpan.', $e));
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

        $lastkodeizin = $lastizin != null ? $lastizin->kode_izin : '';
        $format = 'IZ'.$bulan.$thn;
        $kode_izin = $this->izin->generateKodeIzin($lastkodeizin, $format, 4);

        $data = [
            'kode_izin' => $kode_izin,
            'nik' => $nik,
            'tgl_izin_dari' => $tgl_izin_dari,
            'tgl_izin_sampai' => $tgl_izin_sampai,
            'status' => $status,
            'keterangan' => $keterangan,
        ];

        try {
            Izin::create($data);

            return redirect('/presensi/izin')->with('success', 'Pengajuan Izin Terlambat Berhasil Disimpan. Kode Izin: '.$kode_izin);
        } catch (\Exception $e) {
            return redirect('/presensi/izin')->with('error', $this->failMessage('Data Gagal Disimpan.', $e));
        }
    }

    public function storeizinpulangcepat(Request $request)
    {
        $nik = Auth::guard('karyawan')->user()->nik;
        $tanggal = date('Y-m-d');

        $presensiHariIni = Presensi::where('nik', $nik)
            ->whereDate('tgl_presensi', $tanggal)
            ->first();

        if (! $presensiHariIni || empty($presensiHariIni->jam_in) || $presensiHariIni->jam_in == '00:00:00') {
            return redirect('/presensi/izin')->with('error', 'Pengajuan pulang cepat hanya bisa dilakukan setelah absen masuk.');
        }

        if (! empty($presensiHariIni->jam_out) && $presensiHariIni->jam_out != '00:00:00') {
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

        $lastkodeizin = $lastizin != null ? $lastizin->kode_izin : '';
        $format = 'IZ'.$bulan.$thn;
        $kode_izin = $this->izin->generateKodeIzin($lastkodeizin, $format, 4);

        $data = [
            'kode_izin' => $kode_izin,
            'nik' => $nik,
            'tgl_izin_dari' => $tanggal,
            'tgl_izin_sampai' => $tanggal,
            'status' => 'p',
            'keterangan' => $keterangan,
        ];

        try {
            Izin::create($data);

            return redirect('/presensi/izin')->with('success', 'Pengajuan Pulang Cepat Berhasil Disimpan. Kode Izin: '.$kode_izin);
        } catch (\Exception $e) {
            return redirect('/presensi/izin')->with('error', $this->failMessage('Data Gagal Disimpan.', $e));
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
                'kode_cuti' => 'Maaf, Anda memiliki Surat Peringatan (SP) yang masih aktif sehingga tidak dapat mengajukan cuti.',
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
                'selected_dates' => 'Pilih minimal satu tanggal cuti.',
            ]);
        }

        $jml_hari = $selectedDates->count();

        $holidayDates = HariLibur::tanggalBerlaku(
            $karyawan->kode_cabang ?? null,
            $karyawan->kode_dept ?? null,
            $selectedDates->first(),
            $selectedDates->last()
        );

        $selectedHolidayDates = $selectedDates->intersect($holidayDates)->values();

        // Cek Libur Shift Khusus menggunakan JadwalKerjaService (Filter "Terima Beres")
        $finalSelectedDates = collect();
        foreach ($selectedDates as $sd) {
            $namahari = $this->jadwalKerja->namaHari(date('D', strtotime($sd)));
            [$jkObj, $isLiburShift] = $this->jadwalKerja->untukHari($nik, $karyawan->kode_dept, $karyawan->kode_cabang, $namahari);
            if (! $isLiburShift && ! $selectedHolidayDates->contains($sd)) {
                $finalSelectedDates->push($sd);
            }
        }

        if ($finalSelectedDates->isEmpty()) {
            throw ValidationException::withMessages([
                'selected_dates' => 'Seluruh tanggal yang dipilih bertepatan dengan libur / shift libur.',
            ]);
        }

        $jml_hari = $finalSelectedDates->count();

        $existingPresensi = Presensi::where('nik', $nik)
            ->whereIn('tgl_presensi', $finalSelectedDates->all())
            ->whereIn('status', ['h', 'i', 's', 'c', 'r'])
            ->exists();

        $existingIzinOverlap = $this->izin->hasDateConflict($nik, $finalSelectedDates);

        if ($existingPresensi || $existingIzinOverlap) {
            throw ValidationException::withMessages([
                'selected_dates' => 'Sebagian tanggal yang diajukan sudah memiliki presensi / izin lain.',
            ]);
        }

        $masterCutiRec = MasterCuti::find($kode_cuti);
        if ($masterCutiRec && $masterCutiRec->jml_hari && (int) $masterCutiRec->jml_hari > 0) {
            $jatah_cuti = (int) $masterCutiRec->jml_hari;
            $cuti_diambil = $this->izin->cutiTakenDays(Auth::guard('karyawan')->user(), date('Y'), $kode_cuti);
            $sisa_cuti = $jatah_cuti - $cuti_diambil;

            if ($jml_hari > $sisa_cuti) {
                throw ValidationException::withMessages([
                    'jmlhari' => "Sisa jatah cuti untuk jenis {$masterCutiRec->nama_cuti} Anda ({$sisa_cuti} hari) tidak mencukupi untuk {$jml_hari} hari efektif yang diajukan.",
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

            $lastkodeizin = $lastizin != null ? $lastizin->kode_izin : '';
            $format = 'IZ'.$bulan.$thn;
            $kode_izin = $this->izin->generateKodeIzin($lastkodeizin, $format, 4);

            Izin::create([
                'kode_izin' => $kode_izin,
                'nik' => $nik,
                'tgl_izin_dari' => $finalSelectedDates->first(),
                'tgl_izin_sampai' => $finalSelectedDates->last(),
                'status' => 'c',
                'kode_cuti' => $kode_cuti,
                'keterangan' => CutiDatesMeta::append($keterangan, $finalSelectedDates),
            ]);

            return redirect('/presensi/izin')->with('success', 'Pengajuan cuti berhasil disimpan.');
        } catch (ValidationException $e) {
            return Redirect::back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect('/presensi/izin')->with('error', $this->failMessage('Data Gagal Disimpan.', $e));
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
                'selected_dates' => 'Pilih minimal satu tanggal roster.',
            ]);
        }

        $holidayDates = HariLibur::tanggalBerlaku(
            $karyawan->kode_cabang ?? null,
            $karyawan->kode_dept ?? null,
            $selectedDates->first(),
            $selectedDates->last()
        );

        $finalSelectedDates = collect();
        foreach ($selectedDates as $selectedDate) {
            $namaHari = $this->jadwalKerja->namaHari(date('D', strtotime($selectedDate)));
            [$jamKerja, $isLiburShift] = $this->jadwalKerja->untukHari($nik, $karyawan->kode_dept, $karyawan->kode_cabang, $namaHari);

            if (! $isLiburShift && ! in_array($selectedDate, $holidayDates, true)) {
                $finalSelectedDates->push($selectedDate);
            }
        }

        if ($finalSelectedDates->isEmpty()) {
            throw ValidationException::withMessages([
                'selected_dates' => 'Seluruh tanggal yang dipilih bertepatan dengan libur / shift libur.',
            ]);
        }

        $existingPresensi = Presensi::where('nik', $nik)
            ->whereIn('tgl_presensi', $finalSelectedDates->all())
            ->whereIn('status', ['h', 'i', 's', 'c', 'r'])
            ->exists();

        $existingIzinOverlap = $this->izin->hasDateConflict($nik, $finalSelectedDates);

        if ($existingPresensi || $existingIzinOverlap) {
            throw ValidationException::withMessages([
                'selected_dates' => 'Sebagian tanggal yang diajukan sudah memiliki presensi / izin lain.',
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

            $lastkodeizin = $lastizin != null ? $lastizin->kode_izin : '';
            $format = 'IZ'.$bulan.$thn;
            $kode_izin = $this->izin->generateKodeIzin($lastkodeizin, $format, 4);

            Izin::create([
                'kode_izin' => $kode_izin,
                'nik' => $nik,
                'tgl_izin_dari' => $finalSelectedDates->first(),
                'tgl_izin_sampai' => $finalSelectedDates->last(),
                'status' => 'r',
                'kode_cuti' => null,
                'keterangan' => CutiDatesMeta::append($keterangan, $finalSelectedDates),
            ]);

            return redirect('/presensi/izin')->with('success', 'Pengajuan roster berhasil disimpan.');
        } catch (ValidationException $e) {
            return Redirect::back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect('/presensi/izin')->with('error', $this->failMessage('Data Gagal Disimpan.', $e));
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
     * Izin milik karyawan yang login dan masih pending. Semua edit/update wajib lewat sini
     * agar karyawan tidak bisa mengubah pengajuan orang lain atau yang sudah diverifikasi.
     */
    private function findOwnPendingIzin(string $kode_izin, ?string $status = null): ?Izin
    {
        return Izin::where('kode_izin', $kode_izin)
            ->where('nik', Auth::guard('karyawan')->user()->nik)
            ->where('status_approved', 0)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->first();
    }

    private function izinTidakValid()
    {
        return Redirect::back()->with('error', 'Pengajuan tidak ditemukan atau sudah diverifikasi sehingga tidak bisa diubah.');
    }

    /**
     * Simpan surat dokter dengan ekstensi hasil deteksi isi file (bukan dari nama file klien).
     */
    private function storeSuratSakit($file, string $kode_izin): string
    {
        $fileName = $kode_izin.'.'.$file->extension();
        $file->storeAs('uploads/sid', $fileName, 'public');

        return $fileName;
    }

    public function edit($kode_izin)
    {
        $dataizin = Izin::where('kode_izin', $kode_izin)
            ->where('nik', Auth::guard('karyawan')->user()->nik)
            ->first();

        if (! $dataizin) {
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
        $dataizin = $this->findOwnPendingIzin($kode_izin, 'i');
        if (! $dataizin) {
            return Redirect::back()->with('error', 'Data Izin Absen tidak valid.');
        }

        return view('karyawan.pengajuanizin.editizinabsen', compact('dataizin'));
    }

    public function editizinterlambat($kode_izin)
    {
        $dataizin = $this->findOwnPendingIzin($kode_izin, 't');
        if (! $dataizin) {
            return Redirect::back()->with('error', 'Data Izin Terlambat tidak valid.');
        }

        if ($dataizin->status_approved != 0) {
            return Redirect::back()->with('error', 'Pengajuan ini sudah diverifikasi dan tidak bisa diubah.');
        }

        return view('karyawan.pengajuanizin.editizinterlambat', compact('dataizin'));
    }

    public function updateizinabsen($kode_izin, Request $request)
    {
        if (! $this->findOwnPendingIzin($kode_izin, 'i')) {
            return $this->izinTidakValid();
        }

        $request->validate([
            'dari' => 'required|date',
            'sampai' => 'required|date|after_or_equal:dari',
            'keterangan' => 'required|string',
        ]);

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
            return redirect('/presensi/izin')->with('error', $this->failMessage('Data Izin Absen Gagal Diupdate.', $e));
        }
    }

    public function updateizinterlambat($kode_izin, Request $request)
    {
        if (! $this->findOwnPendingIzin($kode_izin, 't')) {
            return $this->izinTidakValid();
        }

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
            return redirect('/pengajuanizin/index')->with('error', $this->failMessage('Data Izin Terlambat Gagal Diupdate.', $e));
        }
    }

    public function editizinpulangcepat($kode_izin)
    {
        $dataizin = $this->findOwnPendingIzin($kode_izin, 'p');
        if (! $dataizin) {
            return Redirect::back()->with('error', 'Data Izin Pulang Cepat tidak valid.');
        }

        if ($dataizin->status_approved != 0) {
            return Redirect::back()->with('error', 'Pengajuan ini sudah diverifikasi dan tidak bisa diubah.');
        }

        return view('karyawan.pengajuanizin.editizinpulangcepat', compact('dataizin'));
    }

    public function updateizinpulangcepat($kode_izin, Request $request)
    {
        if (! $this->findOwnPendingIzin($kode_izin, 'p')) {
            return $this->izinTidakValid();
        }

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
            return redirect('/pengajuanizin/index')->with('error', $this->failMessage('Data Izin Pulang Cepat Gagal Diupdate.', $e));
        }
    }

    public function editizinsakit($kode_izin)
    {
        $dataizin = $this->findOwnPendingIzin($kode_izin, 's');
        if (! $dataizin) {
            return Redirect::back()->with('error', 'Data Izin Sakit tidak valid.');
        }

        if ($dataizin->status_approved != 0) {
            return Redirect::back()->with('error', 'Pengajuan ini sudah diverifikasi dan tidak bisa diubah.');
        }

        return view('karyawan.pengajuanizin.editizinsakit', compact('dataizin'));
    }

    public function updateizinsakit($kode_izin, Request $request)
    {
        $izin = $this->findOwnPendingIzin($kode_izin, 's');
        if (! $izin) {
            return $this->izinTidakValid();
        }

        $request->validate([
            'dari' => 'required|date',
            'sampai' => 'required|date|after_or_equal:dari',
            'keterangan' => 'required|string',
            'sid' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:3072',
        ], [
            'sid.max' => 'Ukuran file tidak boleh lebih dari 3MB.',
            'sid.mimes' => 'File harus berupa gambar (jpg/png) atau PDF.',
        ]);

        $tgl_izin_dari = $request->dari;
        $tgl_izin_sampai = $request->sampai;
        $keterangan = $request->keterangan;
        $old_doc_sid = $izin->doc_sid;

        $data_update = [
            'tgl_izin_dari' => $tgl_izin_dari,
            'tgl_izin_sampai' => $tgl_izin_sampai,
            'keterangan' => $keterangan,
        ];

        try {
            if ($request->hasFile('sid')) {
                // Delete old file if it exists
                $folderPath = 'uploads/sid';
                if (! empty($old_doc_sid) && $old_doc_sid !== '-') {
                    Storage::disk('public')->delete($folderPath.'/'.$old_doc_sid);
                }

                $sid_file_name = $this->storeSuratSakit($request->file('sid'), $kode_izin);

                // Update doc_sid only when the column exists
                if (Schema::hasColumn('izin', 'doc_sid')) {
                    $data_update['doc_sid'] = $sid_file_name;
                }
            }

            Izin::where('kode_izin', $kode_izin)->update($data_update);

            return redirect('/presensi/izin')->with('success', 'Data Izin Sakit Berhasil Diupdate.');
        } catch (\Exception $e) {
            return redirect('/presensi/izin')->with('error', $this->failMessage('Data Izin Sakit Gagal Diupdate.', $e));
        }
    }

    public function editizincuti($kode_izin)
    {
        $nik = Auth::guard('karyawan')->user()->nik;
        $tahun_aktif = date('Y');

        $dataizin = $this->findOwnPendingIzin($kode_izin, 'c')?->load('masterCuti');

        if (! $dataizin) {
            return Redirect::back()->with('error', 'Data Izin Cuti tidak valid.');
        }

        if ($dataizin->status_approved != 0) {
            return Redirect::back()->with('error', 'Pengajuan ini sudah diverifikasi dan tidak bisa diubah.');
        }

        $mastercuti = MasterCuti::orderBy('kode_cuti')->get();
        $sisa_cuti_map = [];
        $kodeCutiTahunan = $this->izin->kodeCutiTahunan();

        foreach ($mastercuti as $mc) {
            $taken = $this->izin->cutiTakenDays(Auth::guard('karyawan')->user(), $tahun_aktif, $mc->kode_cuti, $kode_izin);

            if (empty($mc->jml_hari) || (int) $mc->jml_hari <= 0) {
                $sisa_cuti_map[$mc->kode_cuti] = null;
            } else {
                $sisa = (int) ($mc->jml_hari ?? 0) - (int) $taken;
                $sisa_cuti_map[$mc->kode_cuti] = max(0, $sisa);
            }
        }

        $sisa_cuti = $sisa_cuti_map[$kodeCutiTahunan] ?? null;

        $initialSelectedDates = $dataizin->tanggalDiajukan();
        $keterangan_plain = CutiDatesMeta::strip($dataizin->keterangan);

        return view('karyawan.pengajuanizin.editizincuti', compact('dataizin', 'mastercuti', 'sisa_cuti', 'kodeCutiTahunan', 'sisa_cuti_map', 'kode_izin', 'initialSelectedDates', 'keterangan_plain') + [
            'submissionType' => 'cuti',
        ]);
    }

    public function editizinroster($kode_izin)
    {
        $dataizin = $this->findOwnPendingIzin($kode_izin, 'r');

        if (! $dataizin) {
            return Redirect::back()->with('error', 'Data Pengajuan Roster tidak valid.');
        }

        if ($dataizin->status_approved != 0) {
            return Redirect::back()->with('error', 'Pengajuan ini sudah diverifikasi dan tidak bisa diubah.');
        }

        $initialSelectedDates = $dataizin->tanggalDiajukan();
        $keterangan_plain = CutiDatesMeta::strip($dataizin->keterangan);

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
        if (! $this->findOwnPendingIzin($kode_izin, 'c')) {
            return $this->izinTidakValid();
        }

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
                'kode_cuti' => 'Maaf, Anda memiliki Surat Peringatan (SP) yang masih aktif sehingga tidak dapat mengupdate pengajuan cuti.',
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
                'selected_dates' => 'Pilih minimal satu tanggal cuti.',
            ]);
        }

        $jml_hari = $selectedDates->count();

        $holidayDates = HariLibur::tanggalBerlaku(
            $karyawan->kode_cabang ?? null,
            $karyawan->kode_dept ?? null,
            $selectedDates->first(),
            $selectedDates->last()
        );

        $selectedHolidayDates = $selectedDates->intersect($holidayDates)->values();
        if ($selectedHolidayDates->isNotEmpty()) {
            throw ValidationException::withMessages([
                'selected_dates' => 'Tanggal '.$selectedHolidayDates->join(', ').' adalah hari libur dan tidak dapat diajukan cuti.',
            ]);
        }

        $existingPresensi = Presensi::where('nik', $nik)
            ->whereIn('tgl_presensi', $selectedDates->all())
            ->whereIn('status', ['h', 'i', 's', 'c', 'r'])
            ->exists();

        $existingIzinOverlap = $this->izin->hasDateConflict($nik, $selectedDates, $kode_izin);

        if ($existingPresensi || $existingIzinOverlap) {
            throw ValidationException::withMessages([
                'selected_dates' => 'Sebagian tanggal yang dipilih bentrok dengan presensi/pengajuan izin lain.',
            ]);
        }

        $masterCutiRec = MasterCuti::find($kode_cuti);
        if ($masterCutiRec && $masterCutiRec->jml_hari && (int) $masterCutiRec->jml_hari > 0) {
            $jatah_cuti = (int) $masterCutiRec->jml_hari;
            $cuti_diambil = $this->izin->cutiTakenDays(Auth::guard('karyawan')->user(), date('Y'), $kode_cuti, $kode_izin);
            $sisa_cuti = $jatah_cuti - $cuti_diambil;

            if ($jml_hari > $sisa_cuti) {
                throw ValidationException::withMessages([
                    'jmlhari' => "Sisa jatah cuti untuk jenis {$masterCutiRec->nama_cuti} Anda ({$sisa_cuti} hari) tidak mencukupi untuk {$jml_hari} hari yang diajukan.",
                ]);
            }
        }

        try {
            Izin::where('kode_izin', $kode_izin)->update([
                'tgl_izin_dari' => $selectedDates->first(),
                'tgl_izin_sampai' => $selectedDates->last(),
                'status' => 'c',
                'kode_cuti' => $kode_cuti,
                'keterangan' => CutiDatesMeta::append($keterangan, $selectedDates),
            ]);

            return redirect('/presensi/izin')->with('success', 'Data Pengajuan Cuti Berhasil Diupdate.');
        } catch (ValidationException $e) {
            return Redirect::back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect('/presensi/izin')->with('error', $this->failMessage('Data Pengajuan Cuti Gagal Diupdate.', $e));
        }
    }

    public function updateizinroster($kode_izin, Request $request)
    {
        if (! $this->findOwnPendingIzin($kode_izin, 'r')) {
            return $this->izinTidakValid();
        }

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
                'selected_dates' => 'Pilih minimal satu tanggal roster.',
            ]);
        }

        $holidayDates = HariLibur::tanggalBerlaku(
            $karyawan->kode_cabang ?? null,
            $karyawan->kode_dept ?? null,
            $selectedDates->first(),
            $selectedDates->last()
        );

        $finalSelectedDates = collect();
        foreach ($selectedDates as $selectedDate) {
            $namaHari = $this->jadwalKerja->namaHari(date('D', strtotime($selectedDate)));
            [$jamKerja, $isLiburShift] = $this->jadwalKerja->untukHari($nik, $karyawan->kode_dept, $karyawan->kode_cabang, $namaHari);

            if (! $isLiburShift && ! in_array($selectedDate, $holidayDates, true)) {
                $finalSelectedDates->push($selectedDate);
            }
        }

        if ($finalSelectedDates->isEmpty()) {
            throw ValidationException::withMessages([
                'selected_dates' => 'Seluruh tanggal yang dipilih bertepatan dengan libur / shift libur.',
            ]);
        }

        $existingPresensi = Presensi::where('nik', $nik)
            ->whereIn('tgl_presensi', $finalSelectedDates->all())
            ->whereIn('status', ['h', 'i', 's', 'c', 'r'])
            ->exists();

        $existingIzinOverlap = $this->izin->hasDateConflict($nik, $finalSelectedDates, $kode_izin);

        if ($existingPresensi || $existingIzinOverlap) {
            throw ValidationException::withMessages([
                'selected_dates' => 'Sebagian tanggal yang dipilih bentrok dengan presensi/pengajuan izin lain.',
            ]);
        }

        try {
            Izin::where('kode_izin', $kode_izin)->update([
                'tgl_izin_dari' => $finalSelectedDates->first(),
                'tgl_izin_sampai' => $finalSelectedDates->last(),
                'status' => 'r',
                'kode_cuti' => null,
                'keterangan' => CutiDatesMeta::append($keterangan, $finalSelectedDates),
            ]);

            return redirect('/presensi/izin')->with('success', 'Data Pengajuan Roster Berhasil Diupdate.');
        } catch (ValidationException $e) {
            return Redirect::back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect('/presensi/izin')->with('error', $this->failMessage('Data Pengajuan Roster Gagal Diupdate.', $e));
        }
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
            if (Schema::hasColumn('izin', 'doc_sid') && ! empty($izin->doc_sid) && $izin->doc_sid !== '-') {
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
