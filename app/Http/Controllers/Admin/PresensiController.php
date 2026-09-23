<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\Departemen;
use App\Models\DinasLuar;
use App\Models\HariLibur;
use App\Models\Izin;
use App\Models\Jabatan;
use App\Models\JamKerja;
use App\Models\Karyawan;
use App\Models\KonfigurasiJkDeptDetail;
use App\Models\Presensi;
use App\Models\Setjamkerja;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class PresensiController extends Controller
{
    private const IZIN_TERLAMBAT_DEDUCTION_MINUTES = 10;

    private function isMultiDateIzinStatus(?string $status): bool
    {
        return in_array($status, ['c', 'r'], true);
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

        // Fallback untuk format lama/kurang rapi: ambil tanggal ISO dari keterangan.
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
            $nextExpected = date('Y-m-d', strtotime($segmentEnd.' +1 day'));

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

            return $format($start).' s/d '.$format($end);
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

    private function getDatesFromRange($fromDate, $toDate): array
    {
        if (empty($fromDate)) {
            return [];
        }

        $result = [];
        try {
            $start = new DateTime($fromDate);
            $end = new DateTime($toDate ?: $fromDate);
            if ($end < $start) {
                $end = new DateTime($fromDate);
            }

            $period = new DatePeriod($start, new DateInterval('P1D'), (clone $end)->modify('+1 day'));
            foreach ($period as $date) {
                $result[] = $date->format('Y-m-d');
            }
        } catch (\Exception $e) {
            return [];
        }

        return $result;
    }

    private function getRequestedCutiDates(Izin $izin): array
    {
        $metaDates = $this->parseCutiDatesMeta($izin->keterangan);
        if (! empty($metaDates)) {
            return $metaDates;
        }

        return $this->getDatesFromRange($izin->tgl_izin_dari, $izin->tgl_izin_sampai);
    }

    public function monitoring()
    {
        $departemen = Departemen::orderBy('nama_dept')->get();
        $cabang = Cabang::orderBy('nama_cabang')->get();
        $jabatans = Jabatan::whereHas('role', function ($q) {
            $q->where('guard_name', 'karyawan');
        })->orderBy('nama_jabatan')->get();

        return view('admin.presensi.monitoring', compact('departemen', 'cabang', 'jabatans'));
    }

    public function getpresensi(Request $request)
    {
        $tanggal = $request->tanggal;

        // Konversi format dd-mm-yyyy ke Y-m-d jika diperlukan
        if (! empty($tanggal) && strpos($tanggal, '-') !== false && strlen($tanggal) == 10) {
            $parts = explode('-', $tanggal);
            if (count($parts) == 3 && strlen($parts[2]) == 4) {
                $tanggal = $parts[2].'-'.$parts[1].'-'.$parts[0]; // yyyy-mm-dd
            }
        }
        $kode_dept = $request->kode_dept;
        $loggedInUser = Auth::guard('user')->user();

        // Cek role admin cabang
        $isAdminCabang = $loggedInUser && method_exists($loggedInUser, 'hasRole') && $loggedInUser->hasRole('admin cabang');
        $kode_cabang = $isAdminCabang ? ($loggedInUser->kode_cabang ?? null) : $request->kode_cabang;

        $search = $request->search;
        $hariNama = $this->gethari(date('D', strtotime($tanggal)));

        // Hindari duplikasi baris akibat multi-record izin/dinas pada tanggal yang sama.
        $dinasLuarSubquery = DB::table('dinas_luar')
            ->select('nik', DB::raw('MAX(id) as latest_dinas_luar_id'))
            ->where('status_acc', 'acc')
            ->whereRaw('? between tgl_mulai and tgl_selesai', [$tanggal])
            ->groupBy('nik');

        $izinSubquery = DB::table('izin')
            ->select('nik', DB::raw('MAX(kode_izin) as latest_izin_kode'))
            ->whereIn('status', ['s', 'i', 'c', 'r'])
            ->where('status_approved', 1)
            ->whereRaw('? between tgl_izin_dari and tgl_izin_sampai', [$tanggal])
            ->groupBy('nik');

        $presensiSubquery = DB::table('presensi')
            ->select('nik', DB::raw('MAX(id) as latest_presensi_id'))
            ->where('tgl_presensi', $tanggal)
            ->groupBy('nik');

        $query = Karyawan::query()
            ->wajibPresensi()
            ->where('karyawan.status_aktif', Karyawan::STATUS_AKTIF)
            ->select([
                'karyawan.nik',
                'karyawan.nama_lengkap as nama_karyawan',
                'karyawan.kode_cabang',
                'karyawan.kode_dept',
                'departemen.nama_dept',
                'presensi_filtered.jam_in',
                'presensi_filtered.jam_out',
                'presensi_filtered.foto_in',
                'presensi_filtered.foto_out',
                'presensi_filtered.lokasi_in',
                'presensi_filtered.lokasi_out',
                'presensi_filtered.status',
                'presensi_filtered.id',
                'presensi_filtered.tgl_presensi',
                'jam_kerja.jam_masuk',
                'jam_kerja.akhir_jam_masuk',
                'jam_kerja.jam_pulang',
                'jam_kerja.nama_jam_kerja',
            ])
            ->leftJoin('departemen', 'karyawan.kode_dept', '=', 'departemen.kode_dept')
            ->leftJoin('cabang', 'karyawan.kode_cabang', '=', 'cabang.kode_cabang')
            ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
            ->leftJoinSub($presensiSubquery, 'presensi_map', function ($join) {
                $join->on('karyawan.nik', '=', 'presensi_map.nik');
            })
            ->leftJoin('presensi as presensi_filtered', 'presensi_map.latest_presensi_id', '=', 'presensi_filtered.id')
            ->leftJoin('jam_kerja', 'presensi_filtered.kode_jam_kerja', '=', 'jam_kerja.kode_jam_kerja')
            ->leftJoinSub($dinasLuarSubquery, 'dinas_luar_map', function ($join) {
                $join->on('karyawan.nik', '=', 'dinas_luar_map.nik');
            })
            ->leftJoin('dinas_luar as dinas_luar_filtered', 'dinas_luar_map.latest_dinas_luar_id', '=', 'dinas_luar_filtered.id')
            ->leftJoinSub($izinSubquery, 'izin_map', function ($join) {
                $join->on('karyawan.nik', '=', 'izin_map.nik');
            })
            ->leftJoin('izin as izin_filtered', 'izin_map.latest_izin_kode', '=', 'izin_filtered.kode_izin')
            ->addSelect('cabang.nama_cabang')
            ->addSelect('jabatan.nama_jabatan as jabatan_nama')
            ->addSelect('dinas_luar_filtered.id as dinas_luar_id')
            ->addSelect('izin_filtered.status as izin_status')
            ->addSelect('izin_filtered.kode_izin as izin_kode');

        if (Schema::hasColumn('izin', 'doc_sid')) {
            $query->addSelect('izin_filtered.doc_sid');
        }

        // Filter Cabang & Dept
        if (! empty($kode_dept)) {
            $query->where('karyawan.kode_dept', $kode_dept);
        }
        if (! empty($kode_cabang)) {
            $query->where('karyawan.kode_cabang', $kode_cabang);
        }

        // Filter Jabatan (single)
        if ($request->filled('jabatan_id')) {
            $query->where('karyawan.jabatan_id', $request->jabatan_id);
        }

        // Filter Pencarian Nama/NIK
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(karyawan.nik) like ?', ['%'.strtolower($search).'%'])
                    ->orWhereRaw('LOWER(karyawan.nama_lengkap) like ?', ['%'.strtolower($search).'%']);
            });
        }

        // Filter Status Presensi
        $statusFilter = $request->status ?? null;
        if (! empty($statusFilter)) {
            if ($statusFilter === 'late') {
                $query->whereNotNull('presensi_filtered.jam_in')
                    ->where('presensi_filtered.jam_in', '!=', '00:00:00')
                    ->whereNotNull('jam_kerja.jam_masuk')
                    ->whereRaw('presensi_filtered.jam_in > jam_kerja.jam_masuk')
                    ->whereNull('dinas_luar_filtered.id');
            } elseif ($statusFilter === 'h') {
                $query->whereNotNull('presensi_filtered.jam_in')
                    ->where('presensi_filtered.jam_in', '!=', '00:00:00')
                    ->where(function ($q) {
                        $q->whereRaw('presensi_filtered.jam_in <= jam_kerja.jam_masuk')
                            ->orWhereNull('jam_kerja.jam_masuk');
                    })
                    ->whereNull('dinas_luar_filtered.id');
            } elseif ($statusFilter === 'n' || $statusFilter === 'a') {
                $query->whereNull('presensi_filtered.id')
                    ->whereNull('izin_filtered.kode_izin')
                    ->whereNull('dinas_luar_filtered.id');
            } elseif ($statusFilter === 'd') {
                $query->whereNotNull('dinas_luar_filtered.id');
            } else {
                $query->where(function ($q) use ($statusFilter) {
                    $q->where('presensi_filtered.status', $statusFilter)
                        ->orWhere('izin_filtered.status', $statusFilter);
                });
            }
        }

        $presensi = $query->orderBy('karyawan.nama_lengkap')->paginate(25);
        $presensi->appends($request->all());

        // Transform Data (Logic Penentuan Status)
        $presensi->getCollection()->transform(function ($item) use ($tanggal, $hariNama) {
            // Jika data presensi kosong (belum absen/alpha/izin/dinas)
            if ($item->id === null) {
                $item->tgl_presensi = $tanggal;

                // Cari Jam Kerja Default jika kosong
                if (empty($item->jam_masuk)) {
                    [$jkObj, $isLiburShift] = $this->resolveJamKerja($item->nik, $item->kode_dept, $item->kode_cabang, $hariNama);
                    if ($jkObj) {
                        $item->jam_masuk = $jkObj->jam_masuk;
                        $item->akhir_jam_masuk = $jkObj->akhir_jam_masuk;
                        $item->jam_pulang = $jkObj->jam_pulang;
                        $item->nama_jam_kerja = $jkObj->nama_jam_kerja;
                    }
                    $item->is_libur_shift = $isLiburShift ?? false;
                }

                $isHariLibur = HariLibur::isHariLibur($tanggal, $item->kode_cabang, $item->kode_dept);
                $isJadwalLibur = isset($item->is_libur_shift) && $item->is_libur_shift;

                // Prioritas 1: Dinas Luar
                if (! empty($item->dinas_luar_id)) {
                    $item->status = 'd';
                    $this->setEmptyPresensi($item);
                }
                // Prioritas 2: Libur (Nasional/Shift)
                elseif ($isHariLibur || $isJadwalLibur) {
                    $item->status = 'l';
                    $this->setEmptyPresensi($item);
                }
                // Prioritas 3: Izin/Sakit/Cuti
                elseif (! empty($item->izin_status) && in_array($item->izin_status, ['i', 's', 'c', 'r'])) {
                    $item->status = $item->izin_status;
                    $this->setEmptyPresensi($item);
                }
                // Prioritas 4: Alpha / Belum Absen
                else {
                    if ($tanggal < date('Y-m-d')) {
                        $item->status = 'a';
                    } else {
                        $item->status = 'n';
                    }
                }
            }
            // Jika data presensi ada tapi jam kerja tidak ter-join, fallback ke konfigurasi jadwal
            elseif (empty($item->jam_masuk)) {
                [$jkObj, $isLiburShift] = $this->resolveJamKerja($item->nik, $item->kode_dept, $item->kode_cabang, $hariNama);
                if ($jkObj) {
                    $item->jam_masuk = $jkObj->jam_masuk;
                    $item->akhir_jam_masuk = $jkObj->akhir_jam_masuk;
                    $item->jam_pulang = $jkObj->jam_pulang;
                    $item->nama_jam_kerja = $jkObj->nama_jam_kerja;
                }
                $item->is_libur_shift = $isLiburShift ?? false;
            }
            // Jika sudah ada data presensi tapi status 'd'
            elseif (! empty($item->dinas_luar_id)) {
                $item->status = 'd';
            }
            // Jika hadir (H)
            elseif ($item->status === null && ! empty($item->jam_in) && $item->jam_in != '00:00:00') {
                $item->status = 'h';
            }

            return $item;
        });

        return view('admin.presensi.getpresensi', compact('presensi'));
    }

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

        $hariIni = \Carbon\Carbon::now();
        if ($hariIni->day >= 26) {
            $hariIni->addMonth();
        }
        $defaultBulan = $hariIni->format('n');
        $defaultTahun = $hariIni->format('Y');

        $user = Auth::guard('user')->user();
        $isAdminCabang = $user && method_exists($user, 'hasRole') && $user->hasRole('admin cabang');
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

        $isAdminCabang = $user && method_exists($user, 'hasRole') && $user->hasRole('admin cabang');
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

        $bulan_int = (int) $bulan;
        $tahun_int = (int) $tahun;
        if ($bulan_int == 1) {
            $bulan_awal = 12;
            $tahun_awal = $tahun_int - 1;
        } else {
            $bulan_awal = $bulan_int - 1;
            $tahun_awal = $tahun_int;
        }

        $tgl_awal = "$tahun_awal-".str_pad($bulan_awal, 2, '0', STR_PAD_LEFT).'-26';
        $tgl_akhir = "$tahun_int-".str_pad($bulan_int, 2, '0', STR_PAD_LEFT).'-25';

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

        $hariLiburNasional = $this->getHariLiburData($tgl_awal, $tgl_akhir, $karyawan->kode_cabang, $karyawan->kode_dept);

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

            $hariNama = $this->gethari($dt->format('D'));
            [$jkObj, $isLiburShift] = $this->resolveJamKerja($nik, $karyawan->kode_dept, $karyawan->kode_cabang, $hariNama);

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

        $rekapBulanan = \App\Models\RekapBulanan::where('nik', $nik)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->first();
        $bonusBulanan = $rekapBulanan ? $rekapBulanan->bonus_bulanan : 0;

        $kenaikanGaji = \App\Models\SalaryIncrease::where('nik', $nik)
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

        $hariIni = \Carbon\Carbon::now();
        if ($hariIni->day >= 26) {
            $hariIni->addMonth();
        }
        $defaultBulan = $hariIni->format('n');
        $defaultTahun = $hariIni->format('Y');

        $departemen = Departemen::orderBy('nama_dept')->get();
        $cabang = Cabang::orderBy('nama_cabang')->get();
        $user = Auth::guard('user')->user();
        $isAdminCabang = $user && method_exists($user, 'hasRole') && $user->hasRole('admin cabang');
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

        $isAdminCabang = $user && method_exists($user, 'hasRole') && $user->hasRole('admin cabang');
        if ($isAdminCabang) {
            $kode_cabang = $user->kode_cabang;
        }

        $namabulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $cabang = $kode_cabang ? Cabang::where('kode_cabang', $kode_cabang)->first() : null;

        $bulan_int = (int) $bulan;
        $tahun_int = (int) $tahun;
        if ($bulan_int == 1) {
            $bulan_awal = 12;
            $tahun_awal = $tahun_int - 1;
        } else {
            $bulan_awal = $bulan_int - 1;
            $tahun_awal = $tahun_int;
        }
        $tgl_awal = "$tahun_awal-".str_pad($bulan_awal, 2, '0', STR_PAD_LEFT).'-26';
        $tgl_akhir = "$tahun_int-".str_pad($bulan_int, 2, '0', STR_PAD_LEFT).'-25';

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
                $hariLiburScopeCache[$scopeKey] = $this->getHariLiburData($tgl_awal, $tgl_akhir, $kar->kode_cabang, $kar->kode_dept);
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

        $salaryIncreases = \App\Models\SalaryIncrease::whereIn('nik', $nikList)
            ->where('status', 'approved')
            ->get()
            ->groupBy('nik');

        $rekapBulanan = \App\Models\RekapBulanan::whereIn('nik', $nikList)
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

                $hariNamaNorm = strtolower(trim($this->gethari($dt->format('D'))));
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

    public function izinsakit(Request $request)
    {
        $query = Izin::query()
            ->select(['izin.kode_izin as id', 'izin.tgl_izin_dari', 'izin.tgl_izin_sampai', 'izin.nik', 'karyawan.nama_lengkap', 'karyawan.foto', 'karyawan.kode_cabang', 'izin.status', 'master_cuti.nama_cuti as jenis_cuti_formal', 'izin.kode_cuti', 'izin.status_approved', 'izin.keterangan'])
            ->join('karyawan', 'izin.nik', '=', 'karyawan.nik')
            ->where('karyawan.is_whitelist', 0)
            ->leftJoin('master_cuti', 'izin.kode_cuti', '=', 'master_cuti.kode_cuti')
            ->with('karyawan');

        if (Schema::hasColumn('izin', 'doc_sid')) {
            $query->addSelect('izin.doc_sid');
        }

        $user = Auth::guard('user')->user();
        $isAdminCabang = $user && method_exists($user, 'hasRole') && $user->hasRole('admin cabang');
        $forcedKodeCabang = $isAdminCabang ? ($user->kode_cabang ?? null) : null;

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
        if ($forcedKodeCabang) {
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
            $effectiveDates = $this->isMultiDateIzinStatus($izin->status)
                ? $this->getRequestedCutiDates($izin)
                : $this->getDatesFromRange($izin->tgl_izin_dari, $izin->tgl_izin_sampai);

            $izin->total_hari_view = ! empty($effectiveDates)
                ? count($effectiveDates)
                : 1;

            $izin->keterangan_view = $this->stripCutiDatesMeta($izin->keterangan);
            $izin->requested_dates_view = [];
            $izin->requested_dates_text = '';
            $izin->requested_dates_compact = '';

            if ($this->isMultiDateIzinStatus($izin->status) && ! empty($effectiveDates)) {
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

        $cabang = Cabang::all();
        $jabatans = Jabatan::whereHas('role', function ($q) {
            $q->where('guard_name', 'karyawan');
        })->orderBy('nama_jabatan')->get();

        return view('admin.presensi.izinsakit', compact('izinsakit', 'isAdminCabang', 'cabang', 'jabatans', 'bulan_indo'));
    }

    public function approveizinsakit(Request $request)
    {
        $status_approved = $request->status_approved;
        $id_izinsakit_from = $request->id_izinsakit_from;

        DB::beginTransaction();
        try {
            // Menggunakan where() agar lebih aman daripada find() jika PK bukan integer ID
            $izin = Izin::with('karyawan')->where('kode_izin', $id_izinsakit_from)->first();

            if (! $izin) {
                // Fallback coba pakai find() jika ternyata variable berisi ID integer
                $izin = Izin::with('karyawan')->find($id_izinsakit_from);
            }

            if (! $izin) {
                DB::rollBack();

                return Redirect::back()->with('warning', 'Data tidak ditemukan.');
            }

            if ($this->outsideAdminCabang($izin->karyawan)) {
                DB::rollBack();

                return Redirect::back()->with('warning', 'Anda tidak memiliki akses ke data cabang lain.');
            }

            $statusColumnExists = Schema::hasColumn('izin', 'status_decided_at');
            $previousStatus = (int) $izin->status_approved;
            $newStatus = (int) $status_approved;

            // Validasi: Jika Cuti dan Disetujui, tanggal harus dipilih
            $selectedCutiDates = [];
            if ($request->filled('selected_cuti_dates')) {
                $selectedCutiDates = collect(explode(',', (string) $request->input('selected_cuti_dates')))
                    ->map(function ($date) {
                        return trim($date);
                    })
                    ->filter(function ($date) {
                        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
                    })
                    ->unique()
                    ->values()
                    ->all();
            } elseif ($this->isMultiDateIzinStatus($izin->status)) {
                // Default ke tanggal yang diajukan agar approval tidak perlu pilih satu per satu.
                $selectedCutiDates = $this->getRequestedCutiDates($izin);
            }

            if ($status_approved == 1 && $this->isMultiDateIzinStatus($izin->status) && empty($selectedCutiDates)) {
                DB::rollBack();

                return Redirect::back()->with('warning', 'Harap pilih minimal satu tanggal untuk pengajuan ini.');
            }

            if ($status_approved == 1) {
                // Hapus data presensi lama (jika ada) di rentang tanggal tersebut untuk menghindari duplikat.
                // Untuk izin pulang cepat/terlambat, jangan hapus status hadir karena dipakai sebagai basis update.
                $statusesToDelete = in_array($izin->status, ['p', 't'], true)
                    ? ['t', 'p']
                    : ['i', 's', 'c', 'r', 't', 'p', 'h', 'x'];

                Presensi::where('nik', $izin->nik)
                    ->whereBetween('tgl_presensi', [$izin->tgl_izin_dari, $izin->tgl_izin_sampai])
                    ->whereIn('status', $statusesToDelete)
                    ->delete();

                // Loop Tanggal
                $start = new DateTime($izin->tgl_izin_dari);
                $end = new DateTime($izin->tgl_izin_sampai);
                $end->modify('+1 day');
                $daterange = new DatePeriod($start, new DateInterval('P1D'), $end);

                foreach ($daterange as $date) {
                    $tgl = $date->format('Y-m-d');

                    // Filter Tanggal Cuti (Partial Approval)
                    // Jika ini Cuti, hanya proses tanggal yang dipilih di kalender
                    if ($this->isMultiDateIzinStatus($izin->status) && ! empty($selectedCutiDates)) {
                        if (! in_array($tgl, $selectedCutiDates)) {
                            continue; // Skip tanggal yang tidak dipilih
                        }
                    }

                    $namahari = $this->gethari(date('D', strtotime($tgl)));
                    [$jamKerja] = $this->resolveJamKerja($izin->nik, $izin->karyawan->kode_dept, $izin->karyawan->kode_cabang, $namahari);

                    $kode_jam_kerja = $jamKerja ? $jamKerja->kode_jam_kerja : 'JK01';
                    $jamMasukJadwal = $jamKerja ? $jamKerja->jam_masuk : '00:00:00';

                    if ($izin->status === 'p') {
                        $presensi = Presensi::where('nik', $izin->nik)
                            ->whereDate('tgl_presensi', $tgl)
                            ->orderByDesc('id')
                            ->first();

                        if (! $presensi || empty($presensi->jam_in) || $presensi->jam_in == '00:00:00') {
                            throw new \App\Exceptions\BusinessException('Karyawan belum memiliki data absen masuk untuk diproses pulang cepat.');
                        }

                        if (! empty($presensi->jam_out) && $presensi->jam_out != '00:00:00') {
                            throw new \App\Exceptions\BusinessException('Karyawan sudah absen pulang pada tanggal tersebut.');
                        }

                        $jamPulangJadwal = $jamKerja ? $jamKerja->jam_pulang : '17:00:00';
                        if (! empty($jamPulangJadwal) && strlen($jamPulangJadwal) === 5) {
                            $jamPulangJadwal .= ':00';
                        }

                        if ($jamKerja && empty($presensi->kode_jam_kerja)) {
                            $presensi->kode_jam_kerja = $jamKerja->kode_jam_kerja;
                        }

                        $presensi->status = 'h';
                        $presensi->jam_out = $jamPulangJadwal;
                        $presensi->foto_out = empty($presensi->foto_out) ? '-' : $presensi->foto_out;
                        $presensi->lokasi_out = empty($presensi->lokasi_out) ? '-' : $presensi->lokasi_out;
                        $presensi->save();

                        continue;
                    }

                    if ($izin->status === 't') {
                        // Terlambat
                        $presensi = Presensi::where('nik', $izin->nik)
                            ->whereDate('tgl_presensi', $tgl)
                            ->orderByDesc('id')
                            ->first();

                        if (! $presensi) {
                            $presensi = new Presensi;
                            $presensi->nik = $izin->nik;
                            $presensi->tgl_presensi = $tgl;
                            $presensi->kode_jam_kerja = $kode_jam_kerja;
                            $presensi->foto_in = '-';
                            $presensi->foto_out = null;
                            $presensi->lokasi_in = '-';
                            $presensi->lokasi_out = null;
                        }

                        $jamAcuanIzinTerlambat = ! empty($presensi->jam_in) && $presensi->jam_in !== '00:00:00'
                            ? $presensi->jam_in
                            : $jamMasukJadwal;
                        $jamIzinTerlambat = $this->getJamIzinTerlambat($jamAcuanIzinTerlambat);

                        $presensi->status = 'h';
                        // Koreksi jam_in dengan pengurangan 10 menit khusus izin terlambat.
                        $presensi->jam_in = $jamIzinTerlambat;
                        // Pastikan jika sebelumnya sudah absen pulang (misal diapprove telat banget), jam out tidak tertimpa null, kecuali emang blm pulang.
                        if (empty($presensi->jam_out)) {
                            $presensi->jam_out = null;
                        }
                        $presensi->save();

                        continue;
                    }

                    // Untuk Izin/Sakit/Cuti - buat baru karena data lamanya ('h') sudah dihapus di atas (atau pakai existing objek).
                    $presensi = new Presensi;
                    $presensi->nik = $izin->nik;
                    $presensi->tgl_presensi = $tgl;
                    $presensi->kode_jam_kerja = $kode_jam_kerja;
                    $presensi->foto_in = '-';
                    $presensi->foto_out = '-';
                    $presensi->lokasi_in = '-';
                    $presensi->lokasi_out = '-';
                    $presensi->status = $izin->status;
                    $presensi->jam_in = '00:00:00';
                    $presensi->jam_out = '00:00:00';
                    $presensi->save();
                }

                // Update Data Izin
                if ($this->isMultiDateIzinStatus($izin->status) && ! empty($selectedCutiDates)) {
                    $approvedDaysCount = count($selectedCutiDates);
                    $requestedCutiDates = $this->getRequestedCutiDates($izin);
                    $originalDaysCount = count($requestedCutiDates);

                    if ($approvedDaysCount < $originalDaysCount) {
                        $rejectedDaysCount = $originalDaysCount - $approvedDaysCount;
                        $keterangan = $izin->keterangan ?? '';
                        // Tambahkan note jika belum ada
                        if (! str_contains($keterangan, '[PARTIAL]')) {
                            $newNote = "[PARTIAL] Disetujui: {$approvedDaysCount} dari {$originalDaysCount} hari.";
                            $izin->keterangan = empty($keterangan) ? $newNote : $keterangan.' | '.$newNote;
                        }

                        // Rewrite the metadata completely
                        $clean = preg_replace('/\s*\[CUTI_DATES:[^\]]*\]?\s*/i', ' ', $izin->keterangan);
                        $clean = preg_replace('/\s*\|\s*$/', '', (string) $clean);
                        $clean = trim(preg_replace('/\s{2,}/', ' ', (string) $clean));

                        sort($selectedCutiDates);
                        $izin->keterangan = trim($clean.' [CUTI_DATES:'.implode(',', $selectedCutiDates).']');
                        $izin->tgl_izin_dari = $selectedCutiDates[0];
                        $izin->tgl_izin_sampai = $selectedCutiDates[count($selectedCutiDates) - 1];
                    }
                }

                $izin->status_approved = $status_approved;
                if ($statusColumnExists) {
                    if ($newStatus !== $previousStatus && in_array($newStatus, [1, 2], true)) {
                        $izin->status_decided_at = now();
                    } elseif ($newStatus === 0) {
                        $izin->status_decided_at = null;
                    }
                }
                $izin->catatan_ditolak = null;
                $izin->save();

            } else {
                // LOGIKA DITOLAK/PENDING
                if ($izin->status === 'p') {
                    Presensi::where('nik', $izin->nik)
                        ->whereDate('tgl_presensi', $izin->tgl_izin_dari)
                        ->where('status', 'h')
                        ->update([
                            'jam_out' => null,
                            'foto_out' => '-',
                            'lokasi_out' => '-',
                        ]);
                } elseif ($izin->status === 't') {
                    Presensi::where('nik', $izin->nik)
                        ->whereDate('tgl_presensi', $izin->tgl_izin_dari)
                        ->where('status', 'h')
                        ->update([
                            'jam_in' => null,
                            'foto_in' => '-',
                            'lokasi_in' => '-',
                        ]);
                } else {
                    // Hapus data presensi terkait jika status diubah jadi tolak/pending
                    Presensi::where('nik', $izin->nik)
                        ->whereBetween('tgl_presensi', [$izin->tgl_izin_dari, $izin->tgl_izin_sampai])
                        ->whereIn('status', ['i', 's', 'c', 'r', 't', 'p'])
                        ->delete();
                }

                $izin->status_approved = $status_approved;
                if ($statusColumnExists) {
                    if ($newStatus !== $previousStatus && in_array($newStatus, [1, 2], true)) {
                        $izin->status_decided_at = now();
                    } elseif ($newStatus === 0) {
                        $izin->status_decided_at = null;
                    }
                }
                // Simpan catatan penolakan jika ditolak
                if ($status_approved == 2 && $request->filled('catatan_ditolak')) {
                    $izin->catatan_ditolak = $request->input('catatan_ditolak');
                } elseif ($status_approved != 2) {
                    $izin->catatan_ditolak = null;
                }
                $izin->save();
            }

            DB::commit();

            return Redirect::back()->with('success', 'Data Berhasil Di Update.');
        } catch (\Exception $e) {
            DB::rollBack();

            return Redirect::back()->with('error', $this->failMessage('Gagal memproses data.', $e));
        }
    }

    // Helper function to get approved cuti dates
    private function getApprovedCutiDates($nik, $dateFrom, $dateTo)
    {
        $presensiRecords = Presensi::where('nik', $nik)
            ->where('status', 'c')
            ->whereBetween('tgl_presensi', [$dateFrom, $dateTo])
            ->orderBy('tgl_presensi')
            ->pluck('tgl_presensi')
            ->map(function ($date) {
                // Ensure date is string Y-m-d
                return date('Y-m-d', strtotime($date));
            })
            ->toArray();

        return $presensiRecords;
    }

    public function detailijinsakit($id)
    {
        try {
            $izin = Izin::with(['karyawan', 'karyawan.cabang', 'masterCuti'])->find($id);
            if (! $izin) {
                $izin = Izin::with(['karyawan', 'karyawan.cabang', 'masterCuti'])->where('kode_izin', $id)->first();
            }

            if (! $izin) {
                return response()->json(['error' => 'Data tidak ditemukan'], 404);
            }

            if ($this->outsideAdminCabang($izin->karyawan)) {
                return response()->json(['error' => 'Anda tidak memiliki akses ke data cabang lain.'], 403);
            }

            $dari = \Carbon\Carbon::parse($izin->tgl_izin_dari);
            $sampai = \Carbon\Carbon::parse($izin->tgl_izin_sampai ?? $izin->tgl_izin_dari);

            $requestedDates = [];
            if ($this->isMultiDateIzinStatus($izin->status)) {
                $requestedDates = $this->getRequestedCutiDates($izin);
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

            $jumlahHari = $this->isMultiDateIzinStatus($izin->status) && ! empty($requestedDates)
                ? count($requestedDates)
                : (abs($sampai->diffInDays($dari)) + 1);

            // Fix: Null Coalescing & Optional Helper for Master Cuti
            $nama_cuti = optional($izin->masterCuti)->nama_cuti ?? 'Cuti';

            $jenis_badge = '';
            if ($izin->status == 'i') {
                $jenis_badge = '<span class="badge bg-blue-lt">Izin</span>';
            } elseif ($izin->status == 's') {
                $jenis_badge = '<span class="badge bg-pink-lt">Sakit</span>';
            } elseif ($izin->status == 'r') {
                $jenis_badge = '<span class="badge bg-cyan-lt">Roster</span>';
            } elseif ($izin->status == 't') {
                $jenis_badge = '<span class="badge bg-orange-lt">Izin Terlambat</span>';
            } elseif ($izin->status == 'p') {
                $jenis_badge = '<span class="badge bg-indigo-lt">Pulang Cepat</span>';
            } elseif (! empty($izin->kode_cuti)) {
                $jenis_badge = '<span class="badge bg-teal-lt">'.$nama_cuti.'</span>';
            }

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
            if ($this->isMultiDateIzinStatus($izin->status)) {
                $approved_dates = $this->getApprovedCutiDates($izin->nik, $izin->tgl_izin_dari, $izin->tgl_izin_sampai);
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
                'keterangan' => $this->stripCutiDatesMeta($izin->keterangan),
                'status_badge' => $status_badge,
                'sid_display' => $sid_display,
                'lampiran_link' => $lampiran_link,
                'is_cuti' => $this->isMultiDateIzinStatus($izin->status),
                'status' => $izin->status,
                'status_approved' => (int) $izin->status_approved,
                'catatan_ditolak' => $izin->catatan_ditolak,
                'approved_dates' => $approved_dates,
                'requested_dates' => $this->isMultiDateIzinStatus($izin->status) ? $requestedDates : [],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $this->failMessage('Gagal memproses data.', $e)], 500);
        }
    }

    public function batalkanizinsakit($id)
    {
        DB::beginTransaction();
        try {
            $izin = Izin::find($id);
            if (! $izin) {
                $izin = Izin::where('kode_izin', $id)->first();
            }

            if (! $izin) {
                DB::rollBack();

                return Redirect::back()->with('warning', 'Data tidak ditemukan.');
            }

            if ($this->outsideAdminCabang($izin->karyawan)) {
                DB::rollBack();

                return Redirect::back()->with('warning', 'Anda tidak memiliki akses ke data cabang lain.');
            }
            $wasApproved = ((int) $izin->status_approved === 1);
            $izin->status_approved = 0;
            if (Schema::hasColumn('izin', 'status_decided_at')) {
                $izin->status_decided_at = null;
            }
            // Bersihkan note partial approval jika ada (opsional)
            // $izin->keterangan = str_replace(' | [PARTIAL]...', '', $izin->keterangan);

            $izin->save();

            if ($izin->status === 'p' && $wasApproved) {
                Presensi::where('nik', $izin->nik)
                    ->whereDate('tgl_presensi', $izin->tgl_izin_dari)
                    ->where('status', 'h')
                    ->update([
                        'jam_out' => null,
                        'foto_out' => '-',
                        'lokasi_out' => '-',
                    ]);
            } elseif ($izin->status === 't' && $wasApproved) {
                Presensi::where('nik', $izin->nik)
                    ->whereDate('tgl_presensi', $izin->tgl_izin_dari)
                    ->where('status', 'h')
                    ->update([
                        'jam_in' => null,
                        'foto_in' => '-',
                        'lokasi_in' => '-',
                    ]);
            } else {
                Presensi::where('nik', $izin->nik)
                    ->whereBetween('tgl_presensi', [$izin->tgl_izin_dari, $izin->tgl_izin_sampai])
                    ->whereIn('status', ['i', 's', 'c', 'r', 't', 'p'])
                    ->delete();
            }

            DB::commit();

            return Redirect::back()->with('success', 'Approval dibatalkan.');
        } catch (\Exception $e) {
            DB::rollBack();

            return Redirect::back()->with('error', 'Gagal membatalkan.');
        }
    }

    public function tampilkanpeta(Request $request)
    {
        $id = $request->id;
        $presensi = Presensi::with('karyawan')->findOrFail($id);
        $presensi->kode_cabang = $presensi->karyawan->kode_cabang ?? null;
        $presensi->nama_lengkap = $presensi->karyawan->nama_lengkap ?? null;
        $radius = 50;
        if ($presensi->kode_cabang) {
            $cabang = Cabang::where('kode_cabang', $presensi->kode_cabang)->first();
            $radius = $cabang->radius ?? $radius;
        }

        return view('admin.presensi.showmap', compact('presensi', 'radius'));
    }

    // --- Helper Functions ---
    public function gethari($hari)
    {
        switch ($hari) {
            case 'Sun':
                return 'Minggu';
            case 'Mon':
                return 'Senin';
            case 'Tue':
                return 'Selasa';
            case 'Wed':
                return 'Rabu';
            case 'Thu':
                return 'Kamis';
            case 'Fri':
                return 'Jumat';
            case 'Sat':
                return 'Sabtu';
            default:
                return 'Minggu';
        }
    }

    private function getHariLiburData($tgl_awal, $tgl_akhir, $kode_cabang = null, $kode_dept = null)
    {
        $hariLiburQuery = HariLibur::whereBetween('tanggal_libur', [$tgl_awal, $tgl_akhir]);

        $hariLiburQuery->where(function ($query) use ($kode_cabang, $kode_dept) {
            // A. Global
            $query->where(function ($q) {
                $q->where(function ($c) {
                    $c->whereNull('kode_cabang')->orWhere('kode_cabang', '')->orWhere('kode_cabang', 'Semua Cabang');
                })->where(function ($d) {
                    $d->whereNull('kode_dept')->orWhere('kode_dept', '')->orWhere('kode_dept', 'Semua Departemen');
                });
            });

            // B. Filter Cabang
            if (! empty($kode_cabang)) {
                $query->orWhere(function ($q) use ($kode_cabang) {
                    $q->where(function ($c) use ($kode_cabang) {
                        $c->where('kode_cabang', $kode_cabang)
                            ->orWhereRaw("concat(',', kode_cabang, ',') like ?", ["%,{$kode_cabang},%"]);
                    })->where(function ($d) {
                        $d->whereNull('kode_dept')->orWhere('kode_dept', '')->orWhere('kode_dept', 'Semua Departemen');
                    });
                });
            }

            // C. Filter Dept
            if (! empty($kode_dept)) {
                $query->orWhere(function ($q) use ($kode_dept) {
                    $q->where(function ($d) use ($kode_dept) {
                        $d->where('kode_dept', $kode_dept)
                            ->orWhereRaw("concat(',', kode_dept, ',') like ?", ["%,{$kode_dept},%"]);
                    })->where(function ($c) {
                        $c->whereNull('kode_cabang')->orWhere('kode_cabang', '')->orWhere('kode_cabang', 'Semua Cabang');
                    });
                });
            }

            // D. Spesifik Keduanya
            if (! empty($kode_cabang) && ! empty($kode_dept)) {
                $query->orWhere(function ($q) use ($kode_cabang, $kode_dept) {
                    $q->where(function ($c) use ($kode_cabang) {
                        $c->where('kode_cabang', $kode_cabang)
                            ->orWhereRaw("concat(',', kode_cabang, ',') like ?", ["%,{$kode_cabang},%"]);
                    })->where(function ($d) use ($kode_dept) {
                        $d->where('kode_dept', $kode_dept)
                            ->orWhereRaw("concat(',', kode_dept, ',') like ?", ["%,{$kode_dept},%"]);
                    });
                });
            }
        });

        return $hariLiburQuery->get()->map(function ($item) {
            return date('Y-m-d', strtotime($item->tanggal_libur));
        })->toArray();
    }

    private function resolveJamKerja(?string $nik, ?string $kodeDept, ?string $kodeCabang, string $hari): array
    {
        if (empty($nik) || empty($kodeDept) || empty($kodeCabang)) {
            return [null, false];
        }

        $hariNormal = strtolower(trim($hari));

        // 1. Cek Personal Dulu
        $setJamKerja = Setjamkerja::with('jamKerja')
            ->where('nik', $nik)
            ->where(DB::raw('LOWER(hari)'), $hariNormal)
            ->first();

        if ($setJamKerja) {
            $isLibur = is_null($setJamKerja->kode_jam_kerja) || $setJamKerja->kode_jam_kerja === 'LIBUR';

            return [$setJamKerja->jamKerja, $isLibur];
        }

        // 2. Cek Departemen jika personal tidak ada
        $setJamKerjaDept = KonfigurasiJkDeptDetail::with(['jamKerja', 'konfigurasi'])
            ->where(DB::raw('LOWER(hari)'), $hariNormal)
            ->whereHas('konfigurasi', function ($query) use ($kodeDept, $kodeCabang) {
                $query->where('kode_dept', $kodeDept)->where('kode_cabang', $kodeCabang);
            })->first();

        if ($setJamKerjaDept) {
            $isLibur = is_null($setJamKerjaDept->kode_jam_kerja) || $setJamKerjaDept->kode_jam_kerja === 'LIBUR';

            return [$setJamKerjaDept->jamKerja, $isLibur];
        }

        return [null, false];
    }

    private function setEmptyPresensi($item)
    {
        $item->jam_in = '00:00:00';
        $item->jam_out = '00:00:00';
        $item->foto_in = '-';
        $item->foto_out = '-';
        $item->lokasi_in = '999,999';
        $item->lokasi_out = '999,999';
    }

    private function getJamIzinTerlambat(?string $jamAcuan): string
    {
        if (empty($jamAcuan) || $jamAcuan === '00:00:00') {
            return '00:00:00';
        }

        try {
            $deductionMinutes = (int) get_setting('toleransi_keterlambatan', 10);
            return Carbon::createFromFormat('H:i:s', $jamAcuan)
                ->subMinutes($deductionMinutes)
                ->format('H:i:s');
        } catch (\Exception $e) {
            return $jamAcuan;
        }
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

    public function batalpresensi($id)
    {
        $presensi = Presensi::find($id);
        if (! $presensi) {
            return response()->json(['status' => false, 'message' => 'Data presensi tidak ditemukan.']);
        }

        if ($this->outsideAdminCabang($presensi->karyawan)) {
            return response()->json(['status' => false, 'message' => 'Anda tidak memiliki akses ke data cabang lain.'], 403);
        }

        try {
            // Hapus file foto_in jika ada
            if ($presensi->foto_in && $presensi->foto_in !== '-') {
                Storage::disk('public')->delete('uploads/absensi/'.$presensi->foto_in);
            }
            // Hapus file foto_out jika ada
            if ($presensi->foto_out && $presensi->foto_out !== '-') {
                Storage::disk('public')->delete('uploads/absensi/'.$presensi->foto_out);
            }

            // Update record presensi menjadi anulir (status 'x')
            $presensi->update([
                'jam_in' => '00:00:00',
                'jam_out' => '00:00:00',
                'foto_in' => '-',
                'foto_out' => '-',
                'lokasi_in' => '999,999',
                'lokasi_out' => '999,999',
                'status' => 'x',
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Presensi berhasil dianulir. Karyawan dapat melakukan absen masuk ulang.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $this->failMessage('Gagal memproses data.', $e)]);
        }
    }
}
