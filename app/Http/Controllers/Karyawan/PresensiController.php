<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\DinasLuar;
use App\Models\HariLibur;
use App\Models\Izin;
use App\Models\Karyawan;
use App\Models\KPIDaily;
use App\Models\KPIMaster;
use App\Models\LeaderboardSnapshot;
use App\Models\Lembur;
use App\Models\Presensi;
use App\Services\JadwalKerjaService;
use App\Services\PresensiService;
use App\Support\FotoBase64;
use App\Support\PeriodeKerja;
use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PresensiController extends Controller
{
    public function __construct(
        private JadwalKerjaService $jadwalKerja,
        private PresensiService $presensi,
    ) {}

    public function create()
    {
        $karyawan = Auth::guard('karyawan')->user();
        $nik = $karyawan->nik;

        if ((int) $karyawan->is_whitelist === 1) {
            return redirect()
                ->route('dashboard.karyawan')
                ->with('error', 'Akun Anda dikecualikan dari sistem presensi harian.');
        }

        if (in_array($karyawan->status_aktif, Karyawan::TURNOVER_STATUSES)) {
            return redirect()
                ->route('dashboard.karyawan')
                ->with('error', 'Akun Anda tidak aktif dan tidak dapat melakukan presensi.');
        }

        $tanggal_sekarang = date('Y-m-d');
        $libur = $this->presensi->statusLibur($karyawan, $tanggal_sekarang);

        if ($libur['libur']) {
            // Tampilkan keterangan libur yang paling spesifik (cabang+dept > cabang/dept > umum).
            $hariLiburInfo = $libur['nasional']
                ? HariLibur::where('tanggal_libur', $tanggal_sekarang)
                    ->berlakuUntuk($karyawan->kode_cabang, $karyawan->kode_dept)
                    ->orderByRaw("(case when kode_cabang is null or kode_cabang = '' then 0 else 1 end + case when kode_dept is null or kode_dept = '' then 0 else 1 end) desc")
                    ->first()
                : null;
            $jenisLibur = $libur['nasional'] ? 'nasional' : 'jam_kerja';
            $isLiburJamKerja = $libur['shift'];

            return view('karyawan.presensi.harilibur', compact('hariLiburInfo', 'jenisLibur', 'isLiburJamKerja'));
        }

        $harini = $this->presensi->tanggalPresensiAktif($nik);

        $cek = Presensi::where('nik', $nik)
            ->where('tgl_presensi', $harini)
            ->where('status', '!=', 'x')
            ->orderByDesc('id')
            ->first();
        $namahari = $this->jadwalKerja->namaHari(date('D', strtotime($harini)));

        $kode_cabang_user = $karyawan->kode_cabang;
        $lokasi_kantor_cabang = Cabang::find($kode_cabang_user);

        if (! $lokasi_kantor_cabang) {
            return redirect('/dashboard')->with('error', 'Konfigurasi lokasi kantor belum diatur!');
        }

        $lokasi_list = $this->presensi->lokasiKantor($kode_cabang_user);

        [$jamkerja] = $this->jadwalKerja->untukHari($nik, $karyawan->kode_dept, $kode_cabang_user, $namahari);

        if ($jamkerja == null) {
            return view('karyawan.presensi.notifjadwal');
        }

        $dinasLuar = DinasLuar::where('nik', $nik)->where('status_acc', 'acc')
            ->whereDate('tgl_mulai', '<=', $harini)->whereDate('tgl_selesai', '>=', $harini)->first();

        return view('karyawan.presensi.create', [
            'cek' => $cek,
            'jamkerja' => $jamkerja,
            'harini' => $harini,
            'dinasLuar' => $dinasLuar,
            'lokasi_kantor' => (object) [
                'lokasi_kantor' => isset($lokasi_list[0]) ? ($lokasi_list[0]['lat'].','.$lokasi_list[0]['lon']) : ($lokasi_kantor_cabang->lokasi_kantor ?? '0,0'),
                'radius' => isset($lokasi_list[0]) ? $lokasi_list[0]['radius'] : ($lokasi_kantor_cabang->radius ?? 0),
            ],
            'lokasi_list' => $lokasi_list,
        ]);
    }

    public function store(Request $request)
    {
        $nik = Auth::guard('karyawan')->user()->nik;
        $lock = Cache::lock('presensi_lock_'.$nik, 10);

        if (! $lock->get()) {
            return response()->json([
                'success' => false,
                'error' => 'Proses presensi sedang berjalan. Tunggu sebentar.',
            ], 429);
        }

        try {
            $karyawan = Auth::guard('karyawan')->user();
            $absenType = $request->input('absen_type');

            if ((int) $karyawan->is_whitelist === 1) {
                return response()->json([
                    'success' => false,
                    'error' => 'Akun Anda dikecualikan dari sistem presensi harian.',
                ], 403);
            }

            if (in_array($karyawan->status_aktif, Karyawan::TURNOVER_STATUSES)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Akun Anda tidak aktif dan tidak dapat melakukan presensi.',
                ], 403);
            }

            $jam = date('H:i:s');
            $jamsekarang = date('H:i');
            $lokasi = (string) $request->lokasi;
            $koordinat = explode(',', $lokasi);

            if (count($koordinat) < 2 || ! is_numeric(trim($koordinat[0])) || ! is_numeric(trim($koordinat[1]))) {
                return response()->json(['success' => false, 'error' => 'Data lokasi tidak valid.'], 400);
            }

            // Foto selfie dikirim sebagai data URL base64; pastikan isinya benar-benar gambar.
            $image_base64 = FotoBase64::decode($request->image);
            if ($image_base64 === null) {
                return response()->json(['success' => false, 'error' => 'Data gambar tidak valid.'], 400);
            }

            if ($this->presensi->statusLibur($karyawan, date('Y-m-d'))['libur']) {
                return response()->json(['success' => false, 'error' => 'Tidak dapat melakukan presensi pada hari libur!'], 403);
            }

            $tgl_presensi = $this->presensi->tanggalPresensiAktif($nik);

            $namahari = $this->jadwalKerja->namaHari(date('D', strtotime($tgl_presensi)));
            [$jamkerja] = $this->jadwalKerja->untukHari($nik, $karyawan->kode_dept, $karyawan->kode_cabang, $namahari);

            if ($jamkerja == null) {
                return response()->json(['success' => false, 'error' => 'Jadwal kerja tidak ditemukan.'], 400);
            }

            $presensi_hari_ini = Presensi::where('nik', $nik)
                ->where('tgl_presensi', $tgl_presensi)
                ->orderByDesc('id')
                ->first();
            $dinasLuarAktif = DinasLuar::forKaryawan($nik, $tgl_presensi)->first();

            // Backward compatible untuk client lama/cached yang belum kirim absen_type.
            if (! in_array($absenType, ['in', 'out'], true)) {
                $absenType = ($presensi_hari_ini && $presensi_hari_ini->status !== 'x' && $presensi_hari_ini->jam_out == null)
                    ? 'out'
                    : 'in';
            }

            if (! $dinasLuarAktif) {
                $lokasiKantor = $this->presensi->lokasiKantor($karyawan->kode_cabang);

                if (! $this->presensi->dalamRadius($lokasiKantor, (float) $koordinat[0], (float) $koordinat[1])) {
                    return response()->json(['success' => false, 'error' => 'Anda berada di luar radius!'], 403);
                }

                if ($absenType === 'in' && (! $presensi_hari_ini || $presensi_hari_ini->status === 'x')) {
                    if ($jamsekarang < $jamkerja->awal_jam_masuk) {
                        return response()->json(['success' => false, 'error' => 'Belum waktunya melakukan presensi.']);
                    } elseif ($jamsekarang > $jamkerja->akhir_jam_masuk) {
                        return response()->json(['success' => false, 'error' => 'Waktu presensi masuk sudah habis.']);
                    }
                }

                if ($absenType === 'out' && (! $presensi_hari_ini || $presensi_hari_ini->status === 'x')) {
                    return response()->json(['success' => false, 'error' => 'Anda belum melakukan presensi masuk.'], 409);
                }

                if ($absenType === 'in' && $presensi_hari_ini && $presensi_hari_ini->status !== 'x') {
                    $errorMessage = ($presensi_hari_ini->jam_out == null)
                        ? 'Anda belum melakukan absen pulang pada sesi sebelumnya!'
                        : 'Anda sudah melakukan presensi masuk dan pulang hari ini.';

                    return response()->json(['success' => false, 'error' => $errorMessage], 409);
                }

                if ($absenType === 'out' && $presensi_hari_ini && $presensi_hari_ini->status !== 'x' && $presensi_hari_ini->jam_out == null) {
                    $tgl_target_pulang = $jamkerja->lintashari == 1 ? date('Y-m-d', strtotime('+1 days', strtotime($tgl_presensi))) : $tgl_presensi;
                    $waktu_jadwal_pulang = $tgl_target_pulang.' '.$jamkerja->jam_pulang;
                    if (strtotime(date('Y-m-d H:i')) < strtotime($waktu_jadwal_pulang)) {
                        return response()->json(['success' => false, 'error' => 'Belum waktunya pulang. Jam pulang: '.$jamkerja->jam_pulang]);
                    }

                    // Wajib isi KPI dulu bila ada master KPI yang berlaku (aturan sama dengan form KPI).
                    if (KPIMaster::untukKaryawan($karyawan)) {
                        $kpiHariIni = KPIDaily::where('nik', $nik)
                            ->whereDate('tanggal', $tgl_presensi)
                            ->first();

                        if (! $kpiHariIni) {
                            return response()->json([
                                'success' => false,
                                'redirect_url' => route('kpi.user.create'),
                                'error' => 'Anda belum bisa absen pulang! Mengalihkan ke form KPI...',
                            ], 403);
                        } elseif ($kpiHariIni->status === 'draft') {
                            return response()->json([
                                'success' => false,
                                'redirect_url' => route('kpi.user.edit', $kpiHariIni->id),
                                'error' => 'KPI Anda masih Draft! Mengalihkan ke form KPI...',
                            ], 403);
                        }
                    }
                }
            }

            // Pasang idempotency guard setelah validasi bisnis, tepat sebelum aksi tulis data.
            $idempotencyFingerprint = hash('sha256', implode('|', [
                $nik,
                $tgl_presensi,
                (string) $absenType,
                (string) $lokasi,
                hash('sha256', (string) $request->image),
            ]));
            $idempotencyKey = 'presensi_idempotency_'.$idempotencyFingerprint;
            if (! Cache::add($idempotencyKey, 1, now()->addSeconds(8))) {
                return response()->json([
                    'success' => false,
                    'error' => 'Permintaan presensi duplikat terdeteksi. Silakan tunggu beberapa detik.',
                ], 429);
            }

            $kejanggalan = $this->presensi->kejanggalanLokasi($nik, $lokasi, $request->akurasi);

            return $this->processPresensi($nik, $tgl_presensi, $jam, $lokasi, $image_base64, $jamkerja, $absenType, $dinasLuarAktif ? true : false, $kejanggalan);
        } finally {
            $lock->release();
        }
    }

    private function processPresensi($nik, $tgl_presensi, $jam, $lokasi, $imageBase64, $jamkerja, $absenType, $isDinasLuar, array $kejanggalan = [])
    {
        $folderPath = 'uploads/absensi/';
        $status_presensi = 'h';
        DB::beginTransaction();
        try {
            // Kunci per karyawan untuk mencegah race condition klik berulang saat jaringan lambat.
            Karyawan::where('nik', $nik)->lockForUpdate()->first();
            $presensiHariIni = Presensi::where('nik', $nik)
                ->where('tgl_presensi', $tgl_presensi)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if ($absenType === 'in') {
                if ($presensiHariIni && $presensiHariIni->status !== 'x') {
                    if ($presensiHariIni->jam_out == null) {
                        DB::rollBack();

                        return response()->json(['success' => false, 'error' => 'Anda belum melakukan absen pulang pada sesi sebelumnya!'], 409);
                    }

                    DB::rollBack();

                    return response()->json(['success' => false, 'error' => 'Anda sudah melakukan presensi masuk dan pulang hari ini.'], 409);
                }

                $fileName = $nik.'_'.$tgl_presensi.'_in.png';
                $data = [
                    'nik' => $nik,
                    'tgl_presensi' => $tgl_presensi,
                    'jam_in' => $jam,
                    'foto_in' => $fileName,
                    'lokasi_in' => $lokasi,
                    'jam_out' => null,
                    'foto_out' => null,
                    'lokasi_out' => null,
                    'kode_jam_kerja' => $jamkerja->kode_jam_kerja,
                    'status' => $status_presensi,
                    'kejanggalan' => $kejanggalan ? 'Masuk: '.implode(', ', $kejanggalan) : null,
                ];

                if ($presensiHariIni && $presensiHariIni->status === 'x') {
                    Presensi::where('id', $presensiHariIni->id)->update($data);
                } else {
                    Presensi::create($data);
                }

                Storage::disk('public')->put($folderPath.$fileName, $imageBase64);

                // AUTO CLOSE LEMBUR JIKA ADA
                $this->presensi->tutupLemburTerbuka($nik, $tgl_presensi, $jam, $fileName, $jamkerja);

                DB::commit();

                return response()->json(['success' => true, 'message' => $isDinasLuar ? 'Presensi Masuk (Dinas) berhasil!' : 'Presensi Masuk berhasil!']);
            }

            if ($absenType === 'out') {
                if (! $presensiHariIni || $presensiHariIni->status === 'x') {
                    DB::rollBack();

                    return response()->json(['success' => false, 'error' => 'Anda belum melakukan presensi masuk.'], 409);
                }

                if ($presensiHariIni->jam_out != null) {
                    DB::rollBack();

                    return response()->json(['success' => false, 'error' => 'Anda sudah melakukan presensi pulang hari ini.'], 409);
                }

                $fileName = $nik.'_'.$tgl_presensi.'_out.png';
                $data = ['jam_out' => $jam, 'foto_out' => $fileName, 'lokasi_out' => $lokasi];
                if ($kejanggalan) {
                    $data['kejanggalan'] = trim($presensiHariIni->kejanggalan.'
Pulang: '.implode(', ', $kejanggalan));
                }
                Presensi::where('id', $presensiHariIni->id)->update($data);
                Storage::disk('public')->put($folderPath.$fileName, $imageBase64);
                DB::commit();

                return response()->json(['success' => true, 'message' => $isDinasLuar ? 'Presensi Pulang (Dinas) berhasil!' : 'Presensi Pulang berhasil!']);
            }

            DB::rollBack();

            return response()->json(['success' => false, 'error' => 'Anda sudah melakukan presensi Masuk dan Pulang hari ini!'], 409);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal Simpan Presensi: '.$e->getMessage());

            return response()->json(['success' => false, 'error' => 'Gagal menyimpan data.'], 500);
        }
    }

    public function histori()
    {
        $namabulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        return view('karyawan.presensi.histori', compact('namabulan'));
    }

    public function gethistori(Request $request)
    {
        $bulan = $request->bulan;
        $tahun = $request->tahun;
        $karyawan = Auth::guard('karyawan')->user();
        $nik = $karyawan->nik;
        $kode_dept = $karyawan->kode_dept;
        $kode_cabang = $karyawan->kode_cabang;

        if (empty($bulan) || empty($tahun)) {
            return view('karyawan.presensi.gethistori', ['histori' => collect([])]);
        }

        $periode = PeriodeKerja::bulan($bulan, $tahun);
        // Mutable: dipakai sebagai iterator harian (addDay) di bawah.
        $periodStart = $periode->mulai->toMutable();
        $periodEnd = $periode->selesai->toMutable()->endOfDay();

        $histori_presensi = Presensi::with(['jamKerja:kode_jam_kerja,jam_masuk'])
            ->whereBetween('tgl_presensi', [$periodStart->format('Y-m-d'), $periodEnd->format('Y-m-d')])
            ->where('nik', $nik)
            ->whereIn('status', ['h', 'x'])
            ->orderBy('tgl_presensi')
            ->get()
            ->map(function ($item) {
                $item->jenis = 'presensi';
                $item->jam_masuk_jadwal = optional($item->jamKerja)->jam_masuk;

                return $item;
            });

        $histori_izin = Izin::with('masterCuti')
            ->where('nik', $nik)
            ->whereDate('tgl_izin_dari', '<=', $periodEnd->toDateString())
            ->whereDate('tgl_izin_sampai', '>=', $periodStart->toDateString())
            ->where('status_approved', 1)
            ->orderBy('tgl_izin_dari')
            ->get();

        $tanggalPulangCepat = $histori_izin
            ->where('status', 'p')
            ->pluck('tgl_izin_dari')
            ->map(function ($date) {
                return date('Y-m-d', strtotime($date));
            })
            ->unique()
            ->values()
            ->toArray();

        $histori_presensi = $histori_presensi->map(function ($item) use ($tanggalPulangCepat) {
            $tglPresensi = date('Y-m-d', strtotime($item->tgl_presensi));
            $item->is_pulang_cepat = in_array($tglPresensi, $tanggalPulangCepat);

            return $item;
        });

        $histori_dinas = DinasLuar::where('nik', $nik)
            ->where('status_acc', 'acc')
            ->whereDate('tgl_mulai', '<=', $periodEnd->toDateString())
            ->whereDate('tgl_selesai', '>=', $periodStart->toDateString())
            ->orderBy('tgl_mulai')
            ->get();

        $histori_normalized = collect();

        foreach ($histori_izin as $izin) {
            if ($izin->status === 'p') {
                continue;
            }
            try {
                $start = new DateTime($izin->tgl_izin_dari);
                $end = new DateTime($izin->tgl_izin_sampai);
                $end->modify('+1 day');
                $daterange = new DatePeriod($start, new DateInterval('P1D'), $end);
                foreach ($daterange as $date) {
                    $tgl = $date->format('Y-m-d');
                    if ($date >= $periodStart && $date <= $periodEnd) {
                        $existsInPresensi = $histori_presensi->contains(function ($item) use ($tgl) {
                            return date('Y-m-d', strtotime($item->tgl_presensi)) === $tgl;
                        });

                        if (! $existsInPresensi) {
                            $histori_normalized->push((object) [
                                'tgl_presensi' => $tgl,
                                'jam_in' => '00:00:00',
                                'jam_out' => '00:00:00',
                                'foto_in' => '-',
                                'foto_out' => '-',
                                'status' => $izin->status,
                                'keterangan' => $izin->keterangan,
                                'nama_cuti' => $izin->masterCuti->nama_cuti ?? null,
                                'jenis' => 'izin',
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        foreach ($histori_dinas as $dinas) {
            try {
                $start = new DateTime($dinas->tgl_mulai);
                $end = new DateTime($dinas->tgl_selesai);
                $end->modify('+1 day');
                $daterange = new DatePeriod($start, new DateInterval('P1D'), $end);
                foreach ($daterange as $date) {
                    $tgl = $date->format('Y-m-d');
                    if ($date >= $periodStart && $date <= $periodEnd) {
                        $existsInPresensi = $histori_presensi->contains(function ($item) use ($tgl) {
                            return date('Y-m-d', strtotime($item->tgl_presensi)) === $tgl;
                        });
                        $existsInNormalized = $histori_normalized->contains('tgl_presensi', $tgl);

                        if (! $existsInPresensi && ! $existsInNormalized) {
                            $histori_normalized->push((object) [
                                'tgl_presensi' => $tgl,
                                'jam_in' => '00:00:00',
                                'jam_out' => '00:00:00',
                                'foto_in' => '-',
                                'foto_out' => '-',
                                'status' => 'd',
                                'keterangan' => $dinas->keterangan ?? $dinas->alasan ?? '-',
                                'nama_cuti' => null,
                                'jenis' => 'dinas_luar',
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        $leaderboard = LeaderboardSnapshot::where('nik', $nik)
            ->whereBetween('date', [$periodStart->format('Y-m-d'), $periodEnd->format('Y-m-d')])
            ->get()
            ->groupBy(function ($item) {
                return Carbon::parse($item->date)->format('Y-m-d');
            });

        $today = clone Carbon::today();
        $tmtStr = $karyawan->tmt ? Carbon::parse($karyawan->tmt)->format('Y-m-d') : null;
        $startIterator = ($tmtStr && $tmtStr > $periodStart->format('Y-m-d')) ? Carbon::parse($tmtStr) : clone $periodStart;
        $endIterator = (clone $periodEnd)->isFuture() ? clone $today : clone $periodEnd;

        // Hanya generate status Alpha jika karyawan TIDAK masuk whitelist
        if ((int) ($karyawan->is_whitelist ?? 0) !== 1) {
            while ($startIterator->lte($endIterator)) {
                $tgl = $startIterator->format('Y-m-d');

                $existsInPresensi = $histori_presensi->contains(function ($item) use ($tgl) {
                    return date('Y-m-d', strtotime($item->tgl_presensi)) === $tgl;
                });
                $existsInNormalized = $histori_normalized->contains('tgl_presensi', $tgl);

                if (! $existsInPresensi && ! $existsInNormalized) {
                    // Verifikasi Jam Kerja dan Hari Libur
                    $isHariLiburNasional = HariLibur::isHariLibur($tgl, $kode_cabang, $kode_dept);
                    $namaHari = $this->jadwalKerja->namaHari(date('D', strtotime($tgl)));
                    [$jkObj, $isLiburJamKerja, $source] = $this->jadwalKerja->untukHari($nik, $kode_dept, $kode_cabang, $namaHari);

                    $isHoliday = $isHariLiburNasional || $isLiburJamKerja || ($namaHari == 'Minggu' && $source == 'none');

                    if (! $isHoliday) {
                        if (! empty($jkObj)) {
                            $jamPulangStr = $jkObj->jam_pulang;
                            $waktuSekarang = Carbon::now();
                            $waktuPulang = Carbon::parse($tgl.' '.$jamPulangStr);

                            // Jika lintas hari, jam pulang adalah di hari berikutnya
                            if ($jkObj->lintashari == 1) {
                                $waktuPulang->addDay();
                            }

                            // Jika sekarang belum melewati jam pulang, jangan anggap Alpha
                            if ($waktuSekarang->lt($waktuPulang)) {
                                $startIterator->addDay();

                                continue;
                            }
                        }

                        $histori_normalized->push((object) [
                            'tgl_presensi' => $tgl,
                            'jam_in' => '00:00:00',
                            'jam_out' => '00:00:00',
                            'foto_in' => '-',
                            'foto_out' => '-',
                            'status' => 'a',
                            'keterangan' => 'Alpha / Mangkir',
                            'nama_cuti' => null,
                            'jenis' => 'alpha',
                        ]);
                    }
                }
                $startIterator->addDay();
            }
        }

        $histori_presensi = $histori_presensi->map(function ($item) use ($leaderboard) {
            $tgl = date('Y-m-d', strtotime($item->tgl_presensi));
            $item->daily_points = $leaderboard->get($tgl)?->first();

            return $item;
        });

        $histori_normalized = $histori_normalized->map(function ($item) use ($leaderboard) {
            $item->daily_points = $leaderboard->get($item->tgl_presensi)?->first();

            return $item;
        });

        $histori = collect($histori_presensi)->merge($histori_normalized)->sortBy('tgl_presensi')->values();

        return view('karyawan.presensi.gethistori', compact('histori'));
    }

    // --- Helper Functions ---

}
