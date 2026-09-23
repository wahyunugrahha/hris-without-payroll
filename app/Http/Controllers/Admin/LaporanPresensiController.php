<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\Departemen;
use App\Models\DinasLuar;
use App\Models\HariLibur;
use App\Models\JamKerja;
use App\Models\Karyawan;
use App\Models\Presensi;
use App\Models\RekapBulanan;
use App\Models\SalaryIncrease;
use App\Models\Setjamkerja;
use App\Services\JadwalKerjaService;
use App\Support\PeriodeKerja;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

/**
 * Laporan presensi per karyawan & rekap presensi per periode (tampilan dan cetak/Excel).
 */
class LaporanPresensiController extends Controller
{
    public function __construct(private JadwalKerjaService $jadwalKerja) {}

    public function laporan()
    {
        $namabulan = [
            '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];

        $list_periode = [];
        for ($i = 1; $i <= 12; $i++) {
            $bulan_lalu = ($i == 1) ? 12 : $i - 1;
            $nama_bln_lalu = $namabulan[$bulan_lalu];
            $nama_bln_ini = $namabulan[$i];
            $list_periode[$i] = "26 $nama_bln_lalu - 25 $nama_bln_ini";
        }

        $periodeIni = PeriodeKerja::dari();
        $defaultBulan = $periodeIni->bulanKe();
        $defaultTahun = $periodeIni->tahun();

        $user = Auth::guard('user')->user();
        $isAdminCabang = (bool) $user?->isAdminCabang();
        $forcedKodeCabang = $isAdminCabang ? ($user->kode_cabang ?? null) : null;

        $karyawan = Karyawan::orderBy('nama_lengkap')
            ->wajibPresensi()
            ->where('status_aktif', Karyawan::STATUS_AKTIF)
            ->when(! empty($forcedKodeCabang), fn ($q) => $q->where('kode_cabang', $forcedKodeCabang))
            ->get();

        $cabang = Cabang::orderBy('nama_cabang')->get();
        $departemen = Departemen::orderBy('nama_dept')->get();

        return view('admin.presensi.laporan', compact(
            'namabulan', 'list_periode', 'defaultBulan', 'defaultTahun',
            'karyawan', 'cabang', 'departemen', 'forcedKodeCabang'
        ));
    }

    public function cetaklaporan(Request $request)
    {
        $nik = $request->nik;
        $bulan = $request->bulan;
        $tahun = $request->tahun;
        $user = Auth::guard('user')->user();

        $isAdminCabang = (bool) $user?->isAdminCabang();
        $forcedKodeCabang = $isAdminCabang ? ($user->kode_cabang ?? null) : null;

        $namabulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        $karyawan = Karyawan::with(['departemen', 'cabang'])
            ->wajibPresensi()
            ->where('nik', $nik)
            ->first();

        if (! $karyawan) {
            return Redirect::back()->with(['warning' => 'Data Karyawan tidak ditemukan.']);
        }

        if ($forcedKodeCabang && ($karyawan->kode_cabang !== $forcedKodeCabang)) {
            return Redirect::back()->with(['warning' => 'Akses ditolak.']);
        }

        $periode = PeriodeKerja::bulan($bulan, $tahun);
        [$tgl_awal, $tgl_akhir] = $periode->range();
        // Dipakai view cetak.
        $bulan_int = $periode->bulanKe();
        $tahun_int = $periode->tahun();
        $bulan_awal = $periode->mulai->month;
        $tahun_awal = $periode->mulai->year;

        // Sesuaikan tanggal efektif jika karyawan baru join setelah tanggal awal periode (TMT)
        if ($karyawan->tmt && $karyawan->tmt->format('Y-m-d') > $tgl_awal) {
            $tgl_awal = $karyawan->tmt->format('Y-m-d');
        }

        $presensiRaw = Presensi::with('jamKerja')
            ->where('nik', $nik)
            ->whereBetween('tgl_presensi', [$tgl_awal, $tgl_akhir])
            ->orderBy('tgl_presensi')->get();

        $dinasLuarData = DinasLuar::query()
            ->where('nik', $nik)
            ->where('status_acc', 'acc')
            ->whereDate('tgl_selesai', '>=', $tgl_awal)
            ->whereDate('tgl_mulai', '<=', $tgl_akhir)
            ->get();

        $dinasLuarDates = [];
        foreach ($dinasLuarData as $dl) {
            $start = new DateTime($dl->tgl_mulai);
            $end = new DateTime($dl->tgl_selesai);
            $end->modify('+1 day');
            $period = new DatePeriod($start, new DateInterval('P1D'), $end);
            foreach ($period as $dt) {
                $dateStr = $dt->format('Y-m-d');
                if ($dateStr >= $tgl_awal && $dateStr <= $tgl_akhir) {
                    $dinasLuarDates[$dateStr] = true;
                }
            }
        }

        $hariLiburNasional = HariLibur::tanggalBerlaku($karyawan->kode_cabang, $karyawan->kode_dept, $tgl_awal, $tgl_akhir);

        $presensiByDate = $presensiRaw->keyBy(fn ($p) => date('Y-m-d', strtotime($p->tgl_presensi)));
        $presensiRows = collect();

        $startDate = new DateTime($tgl_awal);
        $endDate = new DateTime($tgl_akhir);
        $endDate->modify('+1 day');
        $period = new DatePeriod($startDate, new DateInterval('P1D'), $endDate);

        foreach ($period as $dt) {
            $dateStr = $dt->format('Y-m-d');

            if ($presensiByDate->has($dateStr)) {
                $presensiRows->push($presensiByDate->get($dateStr));

                continue;
            }

            if (! empty($dinasLuarDates[$dateStr])) {
                $presensiRows->push((object) [
                    'tgl_presensi' => $dateStr,
                    'jam_in' => '00:00:00', 'jam_out' => '00:00:00',
                    'foto_in' => '-', 'foto_out' => '-',
                    'status' => 'd',
                ]);

                continue;
            }

            $hariNama = $this->jadwalKerja->namaHari($dt->format('D'));
            [$jkObj, $isLiburShift] = $this->jadwalKerja->untukHari($nik, $karyawan->kode_dept, $karyawan->kode_cabang, $hariNama);

            $isMinggu = $dt->format('w') == 0;
            $isLiburNasional = in_array($dateStr, $hariLiburNasional ?? []);

            if ($isLiburShift || $isLiburNasional || ($isMinggu && empty($jkObj))) {
                $presensiRows->push((object) [
                    'tgl_presensi' => $dateStr,
                    'jam_in' => '00:00:00', 'jam_out' => '00:00:00',
                    'foto_in' => '-', 'foto_out' => '-',
                    'status' => 'l',
                ]);

                continue;
            }

            $presensiRows->push((object) [
                'tgl_presensi' => $dateStr,
                'jam_in' => '-', 'jam_out' => '-',
                'foto_in' => '-', 'foto_out' => '-',
                'status' => 'a',
            ]);
        }

        $presensiSorted = $presensiRows->sortBy('tgl_presensi')->values();

        $status_map = [
            'h' => 'Hadir', 'i' => 'Izin', 's' => 'Sakit',
            'c' => 'Cuti', 'd' => 'Dinas', 'l' => 'Libur',
            'a' => 'Alpha',
        ];

        $presensiFinal = $presensiSorted->map(function ($item) use ($status_map, $karyawan) {
            $item->keterangan = $status_map[$item->status] ?? $item->status;
            $jam_in = $item->jam_in ?? '00:00:00';
            $jam_out = $item->jam_out ?? '00:00:00';

            $jam_pulang_resmi = $karyawan->jam_pulang ?? '17:00:00';

            if ($item->status == 'h' && $jam_out != '00:00:00') {
                $item->total_jam_kerja = $this->hitungSelisih($jam_in, $jam_out);
            } else {
                $item->total_jam_kerja = '-';
            }

            $item->foto_in_url = (! empty($item->foto_in) && $item->foto_in != '-' && Storage::disk('public')->exists('uploads/absensi/'.$item->foto_in))
                ? asset('storage/uploads/absensi/'.$item->foto_in)
                : null;

            $item->foto_out_url = (! empty($item->foto_out) && $item->foto_out != '-' && Storage::disk('public')->exists('uploads/absensi/'.$item->foto_out))
                ? asset('storage/uploads/absensi/'.$item->foto_out)
                : null;

            return $item;
        });

        $path_foto_karyawan = 'uploads/karyawan/'.$karyawan->foto;
        $karyawan->foto_url = (! empty($karyawan->foto) && Storage::disk('public')->exists($path_foto_karyawan))
            ? asset('storage/'.$path_foto_karyawan)
            : asset('assets/img/nophoto.png');

        $user = Auth::user();
        $approverName = $user ? $user->name : 'Ahmad Yozi Alhidayah';
        $approverJabatan = $user && $user->jabatan ? strtoupper($user->jabatan->nama_jabatan) : 'HRD';

        $approvedAt = Carbon::now()->translatedFormat('d F Y');

        $ttdText = "Telah ditandatangani oleh: {$approverName}, Jabatan: {$approverJabatan}, Tanggal: {$approvedAt}, Validasi: SAH";
        $ttdUrl = url('/ttd').'?token='.base64_encode($ttdText);
        $qrImage = 'https://api.qrserver.com/v1/create-qr-code/?size=120x120&data='.urlencode($ttdUrl);

        $cabang = $karyawan->cabang;

        $rekapBulanan = RekapBulanan::where('nik', $nik)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->first();
        $bonusBulanan = $rekapBulanan ? $rekapBulanan->bonus_bulanan : 0;

        $kenaikanGaji = SalaryIncrease::where('nik', $nik)
            ->where('status', 'approved')
            ->orderBy('approved_at', 'desc')
            ->first();

        $data = compact(
            'nik', 'bulan', 'tahun', 'namabulan', 'karyawan',
            'presensiFinal', 'cabang', 'tgl_awal', 'tgl_akhir',
            'approverName', 'approverJabatan', 'approvedAt', 'qrImage',
            'bonusBulanan', 'kenaikanGaji'
        );

        if ($request->has('exportexcel')) {
            $time = date('d-m-Y_H-i-s');

            return response()
                ->view('admin.presensi.cetaklaporanexcel', $data)
                ->header('Content-Type', 'application/vnd.ms-excel')
                ->header('Content-Disposition', "attachment; filename=Laporan_Presensi_$time.xls");
        }

        return view('admin.presensi.cetaklaporan', $data);
    }

    public function rekap()
    {
        $namabulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        $list_periode = [];
        for ($i = 1; $i <= 12; $i++) {
            $bulan_lalu = $i == 1 ? 12 : $i - 1;
            $nama_bln_lalu = $namabulan[$bulan_lalu];
            $nama_bln_ini = $namabulan[$i];
            $list_periode[$i] = "26 $nama_bln_lalu - 25 $nama_bln_ini";
        }

        $periodeIni = PeriodeKerja::dari();
        $defaultBulan = $periodeIni->bulanKe();
        $defaultTahun = $periodeIni->tahun();

        $departemen = Departemen::orderBy('nama_dept')->get();
        $cabang = Cabang::orderBy('nama_cabang')->get();
        $user = Auth::guard('user')->user();
        $isAdminCabang = (bool) $user?->isAdminCabang();
        $forcedKodeCabang = $isAdminCabang ? ($user->kode_cabang ?? null) : null;

        return view('admin.presensi.rekap', compact('namabulan', 'list_periode', 'defaultBulan', 'defaultTahun', 'departemen', 'cabang', 'forcedKodeCabang', 'isAdminCabang'));
    }

    public function cetakrekap(Request $request)
    {
        $bulan = $request->bulan;
        $tahun = $request->tahun;
        $kode_dept = $request->kode_dept;
        $kode_cabang = $request->kode_cabang;

        // Normalisasi nilai "semua" agar tidak terbaca sebagai filter spesifik.
        $allDeptTokens = ['', null, 'all', 'semua', 'semua departemen', 'Semua Departemen'];
        $allCabangTokens = ['', null, 'all', 'semua', 'semua cabang', 'Semua Cabang'];
        if (in_array($kode_dept, $allDeptTokens, true)) {
            $kode_dept = null;
        }
        if (in_array($kode_cabang, $allCabangTokens, true)) {
            $kode_cabang = null;
        }

        $user = Auth::guard('user')->user();

        $isAdminCabang = (bool) $user?->isAdminCabang();
        if ($isAdminCabang) {
            $kode_cabang = $user->kode_cabang;
        }

        $namabulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $cabang = $kode_cabang ? Cabang::where('kode_cabang', $kode_cabang)->first() : null;

        $periode = PeriodeKerja::bulan($bulan, $tahun);
        [$tgl_awal, $tgl_akhir] = $periode->range();
        // Dipakai view cetak.
        $bulan_int = $periode->bulanKe();
        $tahun_int = $periode->tahun();
        $bulan_awal = $periode->mulai->month;
        $tahun_awal = $periode->mulai->year;

        // PERBAIKAN: Hanya mengambil karyawan yang statusnya aktif
        $karyawanQuery = Karyawan::query()
            ->wajibPresensi()
            ->where('karyawan.status_aktif', Karyawan::STATUS_AKTIF)
            ->select('karyawan.nik', 'karyawan.nama_lengkap', 'karyawan.kode_cabang', 'karyawan.kode_dept', 'karyawan.tmt', 'departemen.nama_dept', 'cabang.nama_cabang')
            ->leftJoin('departemen', 'karyawan.kode_dept', '=', 'departemen.kode_dept')
            ->leftJoin('cabang', 'karyawan.kode_cabang', '=', 'cabang.kode_cabang');

        if ($kode_cabang) {
            $karyawanQuery->where('karyawan.kode_cabang', $kode_cabang);
        }
        if ($kode_dept) {
            $karyawanQuery->where('karyawan.kode_dept', $kode_dept);
        }

        $karyawans = $karyawanQuery->orderBy('karyawan.nama_lengkap')->get();
        $nikList = $karyawans->pluck('nik');

        $hariLiburByNik = [];
        $hariLiburScopeCache = [];
        foreach ($karyawans as $kar) {
            $scopeKey = ($kar->kode_cabang ?? '').'|'.($kar->kode_dept ?? '');
            if (! array_key_exists($scopeKey, $hariLiburScopeCache)) {
                $hariLiburScopeCache[$scopeKey] = HariLibur::tanggalBerlaku($kar->kode_cabang, $kar->kode_dept, $tgl_awal, $tgl_akhir);
            }
            $hariLiburByNik[$kar->nik] = $hariLiburScopeCache[$scopeKey] ?? [];
        }

        $presensiData = Presensi::whereBetween('tgl_presensi', [$tgl_awal, $tgl_akhir])
            ->whereIn('nik', $nikList)->get()->groupBy('nik');

        $dinasLuarData = DinasLuar::query()
            ->whereIn('nik', $nikList)
            ->where('status_acc', 'acc')
            ->whereDate('tgl_selesai', '>=', $tgl_awal)
            ->whereDate('tgl_mulai', '<=', $tgl_akhir)
            ->get()->groupBy('nik');

        $dinasLuarMap = [];
        foreach ($dinasLuarData as $nik => $records) {
            foreach ($records as $dl) {
                $start = new DateTime($dl->tgl_mulai);
                $end = new DateTime($dl->tgl_selesai);
                $end->modify('+1 day');
                $period = new DatePeriod($start, new DateInterval('P1D'), $end);
                foreach ($period as $dt) {
                    $dateStr = $dt->format('Y-m-d');
                    if ($dateStr >= $tgl_awal && $dateStr <= $tgl_akhir) {
                        $dinasLuarMap[$nik][$dateStr] = true;
                    }
                }
            }
        }

        $allPersonalSet = Setjamkerja::whereIn('nik', $nikList)->get()->groupBy(function ($item) {
            return $item->nik.'-'.strtolower(trim($item->hari));
        });

        $allDeptSet = DB::table('konfigurasi_jk_dept_detail')
            ->join('konfigurasi_jk_dept', 'konfigurasi_jk_dept_detail.kode_jk_dept', '=', 'konfigurasi_jk_dept.kode_jk_dept')
            ->select('konfigurasi_jk_dept_detail.*', 'konfigurasi_jk_dept.kode_cabang', 'konfigurasi_jk_dept.kode_dept')
            ->get()
            ->groupBy(function ($item) {
                return $item->kode_cabang.'-'.$item->kode_dept.'-'.strtolower(trim($item->hari));
            });

        $masterJamKerja = JamKerja::all()->keyBy('kode_jam_kerja');

        $begin = new DateTime($tgl_awal);
        $end = new DateTime($tgl_akhir);
        $end->modify('+1 day');
        $listDates = new DatePeriod($begin, new DateInterval('P1D'), $end);

        $salaryIncreases = SalaryIncrease::whereIn('nik', $nikList)
            ->where('status', 'approved')
            ->get()
            ->groupBy('nik');

        $rekapBulanan = RekapBulanan::whereIn('nik', $nikList)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get()
            ->keyBy('nik');

        $rekap = [];
        foreach ($karyawans as $karyawan) {
            $rekBulan = $rekapBulanan->get($karyawan->nik);
            $totalPoinBulanan = $rekBulan ? $rekBulan->total_poin : 0;

            $karyawanIncrease = $salaryIncreases->get($karyawan->nik);
            $increasePercent = $karyawanIncrease ? $karyawanIncrease->first()->persentase : null;
            $approvedAtDate = $karyawanIncrease ? $karyawanIncrease->first()->approved_at : null;
            $approvedByAdmin = $karyawanIncrease ? $karyawanIncrease->first()->approved_by : null;

            $bonusBulanan = $rekBulan ? $rekBulan->bonus_bulanan : 0;
            $totalPoinKpiBulanan = $rekBulan ? $rekBulan->total_poin_kpi : 0;

            $rekapRow = (object) [
                'nik' => $karyawan->nik,
                'nama_lengkap' => $karyawan->nama_lengkap,
                'nama_dept' => $karyawan->nama_dept,
                'nama_cabang' => $karyawan->nama_cabang,
                'jam_pulang' => null,
                'jam_masuk' => '09:00:00',
                'kenaikan_gaji' => $increasePercent,
                'kenaikan_gaji_tgl' => $approvedAtDate,
                'kenaikan_gaji_oleh' => $approvedByAdmin,
                'bonus_bulanan' => $bonusBulanan,
                'total_poin_bulanan' => $totalPoinBulanan,
                'total_poin_kpi_bulanan' => $totalPoinKpiBulanan,
                'avg_jam_masuk' => $rekBulan ? $rekBulan->avg_jam_masuk : null,
            ];

            $presensis = $presensiData->get($karyawan->nik, collect());
            $presensiByDate = $presensis->keyBy(fn ($p) => date('Y-m-d', strtotime($p->tgl_presensi)));

            foreach ($listDates as $dt) {
                $dateStr = $dt->format('Y-m-d');
                $tglField = 'tgl_'.$dt->format('Ymd');

                if ($karyawan->tmt && $dateStr < $karyawan->tmt->format('Y-m-d')) {
                    $rekapRow->$tglField = '#|00:00:00|00:00:00';

                    continue;
                }

                $hariNamaNorm = strtolower(trim($this->jadwalKerja->namaHari($dt->format('D'))));
                $dayPresensi = $presensiByDate->get($dateStr);

                $personalKey = $karyawan->nik.'-'.$hariNamaNorm;
                $deptKey = $karyawan->kode_cabang.'-'.$karyawan->kode_dept.'-'.$hariNamaNorm;

                $kodeJk = null;
                $isLiburShift = false;
                $hasSchedule = false;

                if ($allPersonalSet->has($personalKey)) {
                    $kodeJk = $allPersonalSet->get($personalKey)->first()->kode_jam_kerja;
                    $isLiburShift = is_null($kodeJk) || $kodeJk === 'LIBUR';
                    $hasSchedule = true;
                } elseif ($allDeptSet->has($deptKey)) {
                    $kodeJk = $allDeptSet->get($deptKey)->first()->kode_jam_kerja;
                    $isLiburShift = is_null($kodeJk) || $kodeJk === 'LIBUR';
                    $hasSchedule = true;
                }

                $jkObj = $kodeJk ? ($masterJamKerja[$kodeJk] ?? null) : null;
                $jamMasukJadwal = $jkObj ? $jkObj->jam_masuk : '09:00:00';

                if ($dt->format('Y-m-d') === $tgl_awal && $jkObj) {
                    $rekapRow->jam_masuk = $jkObj->jam_masuk;
                    $rekapRow->jam_pulang = $jkObj->jam_pulang;
                }

                $isMinggu = $dt->format('w') == 0;
                $isLiburSpesifik = in_array($dateStr, $hariLiburByNik[$karyawan->nik] ?? []);

                if ($dayPresensi) {
                    $jamIn = $dayPresensi->jam_in ?: '00:00:00';
                    $jamOut = $dayPresensi->jam_out ?: '00:00:00';

                    if (! empty($dayPresensi->kode_jam_kerja) && isset($masterJamKerja[$dayPresensi->kode_jam_kerja])) {
                        $jamMasukJadwal = $masterJamKerja[$dayPresensi->kode_jam_kerja]->jam_masuk ?: $jamMasukJadwal;
                    }

                    $rekapRow->$tglField = $dayPresensi->status.'|'.$jamIn.'|'.$jamOut.'|'.$jamMasukJadwal;
                } else {
                    if (! empty($dinasLuarMap[$karyawan->nik][$dateStr])) {
                        $rekapRow->$tglField = 'd|00:00:00|00:00:00';
                    } elseif ($isLiburShift || $isLiburSpesifik || (! $hasSchedule && $isMinggu)) {
                        $rekapRow->$tglField = 'l|00:00:00|00:00:00';
                    } else {
                        $rekapRow->$tglField = null; // Alpha
                    }
                }
            }
            $rekap[] = $rekapRow;
        }

        $data = compact('bulan', 'tahun', 'namabulan', 'rekap', 'kode_dept', 'hariLiburByNik', 'tgl_awal', 'tgl_akhir', 'cabang');

        if ($request->has('exportexcel')) {
            $time = date('d-m-Y H:i:s');

            return response()
                ->view('admin.presensi.cetakrekapexcel', $data)
                ->header('Content-Type', 'application/vnd.ms-excel')
                ->header('Content-Disposition', "attachment; filename=Rekap_Presensi_$time.xls");
        }

        return view('admin.presensi.cetakrekap', $data);
    }

    // --- PRIVATE HELPER METHODS ---

    private function hitungSelisih($jam_masuk, $jam_keluar)
    {
        if (empty($jam_keluar) || empty($jam_masuk) || $jam_keluar === '-' || $jam_masuk === '00:00:00') {
            return '-';
        }

        $dtAwal = new DateTime($jam_masuk);
        $dtAkhir = new DateTime($jam_keluar);

        // Jika jam keluar lebih kecil dari jam masuk (lembur lewat tengah malam)
        if ($dtAkhir < $dtAwal) {
            $dtAkhir->modify('+1 day');
        }

        $diff = $dtAwal->diff($dtAkhir);

        // Format Output: 8j 30m
        return $diff->h.'j '.$diff->i.'m';
    }
}
