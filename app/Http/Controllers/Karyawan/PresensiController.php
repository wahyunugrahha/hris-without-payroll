<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use DateTime;
use DateInterval;
use DatePeriod;

use App\Models\DinasLuar;
use App\Models\Izin;
use App\Models\Lembur;
use App\Models\Presensi;
use App\Models\HariLibur;
use App\Models\Karyawan;
use App\Models\Departemen;
use App\Models\Cabang;
use App\Models\JamKerja;
use App\Models\Setjamkerja;
use App\Models\KonfigurasiJkDept;
use App\Models\KonfigurasiJkDeptDetail;
use App\Models\CabangLokasi;
use App\Models\KPIDaily;
use App\Models\KPIMaster;

class PresensiController extends Controller
{
    public function create()
    {
        $nik = Auth::guard('karyawan')->user()->nik;
        $karyawan = Auth::guard('karyawan')->user();

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
        $jamsekarang = date('H:i');
        $tgl_sebelumnya = date('Y-m-d', strtotime('-1 days', strtotime($tanggal_sekarang)));

        // Cek Libur
        $isHariLiburNasional = HariLibur::isHariLibur($tanggal_sekarang, $karyawan->kode_cabang, $karyawan->kode_dept);
        $hariLiburInfo = null;

        if ($isHariLiburNasional) {
            $hariLiburInfo = HariLibur::where('tanggal_libur', $tanggal_sekarang)
                ->where(function ($query) use ($karyawan) {
                    $query->whereNull('kode_cabang')
                        ->orWhere('kode_cabang', '')
                        ->orWhereRaw("concat(',', kode_cabang, ',') like ?", ["%,{$karyawan->kode_cabang},%"])
                        ->orWhere('kode_cabang', $karyawan->kode_cabang);
                })
                ->where(function ($query) use ($karyawan) {
                    $query->whereNull('kode_dept')
                        ->orWhere('kode_dept', '')
                        ->orWhereRaw("concat(',', kode_dept, ',') like ?", ["%,{$karyawan->kode_dept},%"])
                        ->orWhere('kode_dept', $karyawan->kode_dept);
                })
                ->orderByRaw("(case when kode_cabang is null or kode_cabang = '' then 0 else 1 end + case when kode_dept is null or kode_dept = '' then 0 else 1 end) desc")
                ->first();
        }

        $namaHariSekarang = $this->gethari(date('D', strtotime($tanggal_sekarang)));
        [$jkObj, $isLiburJamKerja] = $this->resolveJamKerja($nik, $karyawan->kode_dept, $karyawan->kode_cabang, $namaHariSekarang);

        if ($isHariLiburNasional || $isLiburJamKerja || ($namaHariSekarang == 'Minggu' && empty($jkObj))) {
            $jenisLibur = $isHariLiburNasional ? 'nasional' : 'jam_kerja';
            return view('karyawan.presensi.harilibur', compact('hariLiburInfo', 'jenisLibur', 'isLiburJamKerja'));
        }

        // Lintas Hari Check
        $cekpresensi_sebelumnya = Presensi::with('jamKerja')
            ->where('tgl_presensi', $tgl_sebelumnya)
            ->where('nik', $nik)
            ->orderByDesc('id')
            ->first();

        $ceklintashari_presensi = $cekpresensi_sebelumnya && $cekpresensi_sebelumnya->jamKerja
            ? $cekpresensi_sebelumnya->jamKerja->lintashari : 0;

        $harini = $tanggal_sekarang;
        if ($ceklintashari_presensi == 1) {
            $nowTs = strtotime(date('Y-m-d H:i'));
            if (!empty($cekpresensi_sebelumnya->jamKerja->jam_pulang)) {
                $waktuPulangPrevTs = strtotime($tgl_sebelumnya . ' ' . $cekpresensi_sebelumnya->jamKerja->jam_pulang) + 24 * 60 * 60;
                if ($nowTs <= $waktuPulangPrevTs) {
                    $harini = $tgl_sebelumnya;
                } elseif ($jamsekarang < $this->batasLintasHari) {
                    $harini = $tgl_sebelumnya;
                }
            } else {
                if ($jamsekarang < $this->batasLintasHari) {
                    $harini = $tgl_sebelumnya;
                }
            }
        }

        $cek = Presensi::where('nik', $nik)
            ->where('tgl_presensi', $harini)
            ->where('status', '!=', 'x')
            ->orderByDesc('id')
            ->first();
        $namahari = $this->gethari(date('D', strtotime($harini)));

        if (!Auth::guard('karyawan')->check()) {
            return redirect('/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $kode_cabang_user = $karyawan->kode_cabang;
        $lokasi_kantor_cabang = Cabang::find($kode_cabang_user);

        if (!$lokasi_kantor_cabang) {
            return redirect('/dashboard')->with('error', 'Konfigurasi lokasi kantor belum diatur!');
        }

        // Lokasi
        $lokasi_list = CabangLokasi::where('kode_cabang', $kode_cabang_user)->where('aktif', true)->get()
            ->map(function ($lok) {
                return [
                    'lat' => (float) $lok->latitude,
                    'lon' => (float) $lok->longitude,
                    'radius' => (int) $lok->radius,
                    'nama' => $lok->nama_lokasi,
                ];
            });

        if ($lokasi_list->isEmpty() && !empty($lokasi_kantor_cabang->lokasi_kantor)) {
            $lok = explode(',', $lokasi_kantor_cabang->lokasi_kantor);
            $lokasi_list = collect([
                [
                    'lat' => (float) ($lok[0] ?? 0),
                    'lon' => (float) ($lok[1] ?? 0),
                    'radius' => (int) ($lokasi_kantor_cabang->radius ?? 0),
                    'nama' => 'Lokasi Utama',
                ]
            ]);
        }

        [$jamkerja,] = $this->resolveJamKerja($nik, $karyawan->kode_dept, $kode_cabang_user, $namahari);

        if ($jamkerja == null) {
            return view("karyawan.presensi.notifjadwal");
        }

        $dinasLuar = DinasLuar::where('nik', $nik)->where('status_acc', 'acc')
            ->whereDate('tgl_mulai', '<=', $harini)->whereDate('tgl_selesai', '>=', $harini)->first();

        return view("karyawan.presensi.create", [
            'cek' => $cek,
            'jamkerja' => $jamkerja,
            'harini' => $harini,
            'dinasLuar' => $dinasLuar,
            'lokasi_kantor' => (object) [
                'lokasi_kantor' => isset($lokasi_list[0]) ? ($lokasi_list[0]['lat'] . ',' . $lokasi_list[0]['lon']) : ($lokasi_kantor_cabang->lokasi_kantor ?? '0,0'),
                'radius' => isset($lokasi_list[0]) ? $lokasi_list[0]['radius'] : ($lokasi_kantor_cabang->radius ?? 0),
            ],
            'lokasi_list' => $lokasi_list,
        ]);
    }

    public function store(Request $request)
    {
        $nik = Auth::guard('karyawan')->user()->nik;
        $lock = Cache::lock('presensi_lock_' . $nik, 10);

        if (!$lock->get()) {
            return response()->json([
                'success' => false,
                'error' => 'Proses presensi sedang berjalan. Tunggu sebentar.'
            ], 429);
        }

        try {
            $karyawan = Auth::guard('karyawan')->user();
            $absenType = $request->input('absen_type');

            if ((int) $karyawan->is_whitelist === 1) {
                return response()->json([
                    'success' => false,
                    'error' => 'Akun Anda dikecualikan dari sistem presensi harian.'
                ], 403);
            }

        if (in_array($karyawan->status_aktif, Karyawan::TURNOVER_STATUSES)) {
            return response()->json([
                'success' => false,
                'error' => 'Akun Anda tidak aktif dan tidak dapat melakukan presensi.'
            ], 403);
        }

        $tanggal_sekarang = date('Y-m-d');
        $jam = date('H:i:s');
        $jamsekarang = date('H:i');
        $lokasi = $request->lokasi;
        $image = $request->image;
        $tgl_sebelumnya = date('Y-m-d', strtotime('-1 days', strtotime($tanggal_sekarang)));

        if (empty($lokasi) || strpos($lokasi, ',') === false)
            return response()->json(['success' => false, 'error' => 'Data lokasi tidak valid.'], 400);
        $image_parts = explode(";base64,", $image);
        if (!isset($image_parts[1]))
            return response()->json(['success' => false, 'error' => 'Data gambar tidak valid.'], 400);
        $image_base64 = base64_decode($image_parts[1]);
        if ($image_base64 === false)
            return response()->json(['success' => false, 'error' => 'Data gambar tidak valid.'], 400);

        $isHariLiburNasional = HariLibur::isHariLibur($tanggal_sekarang, $karyawan->kode_cabang, $karyawan->kode_dept);
        $namaHariSekarang = $this->gethari(date('D', strtotime($tanggal_sekarang)));
        [$jkObj, $isLiburJamKerja] = $this->resolveJamKerja($nik, $karyawan->kode_dept, $karyawan->kode_cabang, $namaHariSekarang);

        // Tambahan blok validasi libur hari ini
        if ($isHariLiburNasional || $isLiburJamKerja || ($namaHariSekarang == 'Minggu' && empty($jkObj))) {
            return response()->json(['success' => false, 'error' => 'Tidak dapat melakukan presensi pada hari libur!'], 403);
        }

        $cekpresensi_sebelumnya = Presensi::with('jamKerja')
            ->where('tgl_presensi', $tgl_sebelumnya)
            ->where('nik', $nik)
            ->orderByDesc('id')
            ->first();
        $ceklintashari_presensi = $cekpresensi_sebelumnya && $cekpresensi_sebelumnya->jamKerja ? $cekpresensi_sebelumnya->jamKerja->lintashari : 0;

        $tgl_presensi = $tanggal_sekarang;
        if ($ceklintashari_presensi == 1) {
            $nowTs = strtotime(date('Y-m-d H:i'));
            if (!empty($cekpresensi_sebelumnya->jamKerja->jam_pulang)) {
                $waktuPulangPrevTs = strtotime($tgl_sebelumnya . ' ' . $cekpresensi_sebelumnya->jamKerja->jam_pulang) + 24 * 60 * 60;
                if ($nowTs <= $waktuPulangPrevTs) {
                    $tgl_presensi = $tgl_sebelumnya;
                } elseif ($jamsekarang < $this->batasLintasHari) {
                    $tgl_presensi = $tgl_sebelumnya;
                }
            } else {
                if ($jamsekarang < $this->batasLintasHari) {
                    $tgl_presensi = $tgl_sebelumnya;
                }
            }
        }

        $namahari = $this->gethari(date('D', strtotime($tgl_presensi)));
        [$jamkerja,] = $this->resolveJamKerja($nik, $karyawan->kode_dept, $karyawan->kode_cabang, $namahari);

        if ($jamkerja == null)
            return response()->json(['success' => false, 'error' => 'Jadwal kerja tidak ditemukan.'], 400);

        $presensi_hari_ini = Presensi::where('nik', $nik)
            ->where('tgl_presensi', $tgl_presensi)
            ->orderByDesc('id')
            ->first();
        $dinasLuarAktif = DinasLuar::forKaryawan($nik, $tgl_presensi)->first();

        // Backward compatible untuk client lama/cached yang belum kirim absen_type.
        if (!in_array($absenType, ['in', 'out'], true)) {
            $absenType = ($presensi_hari_ini && $presensi_hari_ini->status !== 'x' && $presensi_hari_ini->jam_out == null)
                ? 'out'
                : 'in';
        }

        if (!$dinasLuarAktif) {
            $lokasi_cabang_list = CabangLokasi::where('kode_cabang', $karyawan->kode_cabang)->where('aktif', true)->get();
            if ($lokasi_cabang_list->isEmpty()) {
                $cabang = Cabang::find($karyawan->kode_cabang);
                if ($cabang && !empty($cabang->lokasi_kantor)) {
                    $lok = explode(',', $cabang->lokasi_kantor);
                    $obj = new CabangLokasi();
                    $obj->latitude = $lok[0] ?? 0;
                    $obj->longitude = $lok[1] ?? 0;
                    $obj->radius = $cabang->radius ?? 0;
                    $lokasi_cabang_list = collect([$obj]);
                }
            }

            $lokasi_user = explode(",", $lokasi);
            $lat_user = $lokasi_user[0];
            $lon_user = $lokasi_user[1];
            $terdekat = null;

            foreach ($lokasi_cabang_list as $lok) {
                $jarak = $this->distance((float) $lok->latitude, (float) $lok->longitude, $lat_user, $lon_user)['meters'];
                if ($terdekat === null || $jarak < $terdekat['meters']) {
                    $terdekat = ['meters' => $jarak, 'radius' => (int) $lok->radius];
                }
                if ($jarak <= (int) $lok->radius)
                    break;
            }

            if ($terdekat === null || $terdekat['meters'] > $terdekat['radius']) {
                return response()->json(['success' => false, 'error' => 'Anda berada di luar radius!'], 403);
            }

            if ($absenType === 'in' && (!$presensi_hari_ini || $presensi_hari_ini->status === 'x')) {
                if ($jamsekarang < $jamkerja->awal_jam_masuk)
                    return response()->json(['success' => false, 'error' => 'Belum waktunya melakukan presensi.']);
                else if ($jamsekarang > $jamkerja->akhir_jam_masuk)
                    return response()->json(['success' => false, 'error' => 'Waktu presensi masuk sudah habis.']);
            }

            if ($absenType === 'out' && (!$presensi_hari_ini || $presensi_hari_ini->status === 'x')) {
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
                $waktu_jadwal_pulang = $tgl_target_pulang . ' ' . $jamkerja->jam_pulang;
                if (strtotime(date('Y-m-d H:i')) < strtotime($waktu_jadwal_pulang)) {
                    return response()->json(['success' => false, 'error' => 'Belum waktunya pulang. Jam pulang: ' . $jamkerja->jam_pulang]);
                }

                $isKpiActive = KPIMaster::where('is_active', true)
                    ->where('jabatan_id', $karyawan->jabatan_id)
                    ->where('kode_dept', $karyawan->kode_dept)
                    ->where('kode_cabang', $karyawan->kode_cabang)
                    ->exists();

                if ($isKpiActive) {
                    $kpiHariIni = KPIDaily::where('nik', $nik)
                        ->whereDate('tanggal', $tgl_presensi)
                        ->first();

                    if (!$kpiHariIni) {
                        return response()->json([
                            'success' => false, 
                            'redirect_url' => route('kpi.user.create'),
                            'error' => 'Anda belum bisa absen pulang! Mengalihkan ke form KPI...'
                        ], 403);
                    } 
                    elseif ($kpiHariIni->status === 'draft') {
                        return response()->json([
                            'success' => false,
                            'redirect_url' => route('kpi.user.edit', $kpiHariIni->id),
                            'error' => 'KPI Anda masih Draft! Mengalihkan ke form KPI...'
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
                hash('sha256', (string) $image),
            ]));
            $idempotencyKey = 'presensi_idempotency_' . $idempotencyFingerprint;
            if (!Cache::add($idempotencyKey, 1, now()->addSeconds(8))) {
                return response()->json([
                    'success' => false,
                    'error' => 'Permintaan presensi duplikat terdeteksi. Silakan tunggu beberapa detik.',
                ], 429);
            }

            return $this->processPresensi($nik, $tgl_presensi, $jam, $lokasi, $image_base64, $jamkerja, $absenType, $dinasLuarAktif ? true : false);
        } finally {
            $lock->release();
        }
    }

    private function processPresensi($nik, $tgl_presensi, $jam, $lokasi, $imageBase64, $jamkerja, $absenType, $isDinasLuar)
    {
        $folderPath = "uploads/absensi/";
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

                $fileName = $nik . "_" . $tgl_presensi . "_in.png";
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
                    'status' => $status_presensi
                ];
                
                if ($presensiHariIni && $presensiHariIni->status === 'x') {
                    Presensi::where('id', $presensiHariIni->id)->update($data);
                } else {
                    Presensi::create($data);
                }
                
                Storage::disk('public')->put($folderPath . $fileName, $imageBase64);

                // AUTO CLOSE LEMBUR JIKA ADA
                $this->autoCloseLembur($nik, $tgl_presensi, $jam, $fileName, $jamkerja);

                DB::commit();
                return response()->json(['success' => true, 'message' => $isDinasLuar ? 'Presensi Masuk (Dinas) berhasil!' : 'Presensi Masuk berhasil!']);
            }

            if ($absenType === 'out') {
                if (!$presensiHariIni || $presensiHariIni->status === 'x') {
                    DB::rollBack();
                    return response()->json(['success' => false, 'error' => 'Anda belum melakukan presensi masuk.'], 409);
                }

                if ($presensiHariIni->jam_out != null) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'error' => 'Anda sudah melakukan presensi pulang hari ini.'], 409);
                }

                $fileName = $nik . "_" . $tgl_presensi . "_out.png";
                $data = ['jam_out' => $jam, 'foto_out' => $fileName, 'lokasi_out' => $lokasi];
                Presensi::where('id', $presensiHariIni->id)->update($data);
                Storage::disk('public')->put($folderPath . $fileName, $imageBase64);
                DB::commit();
                return response()->json(['success' => true, 'message' => $isDinasLuar ? 'Presensi Pulang (Dinas) berhasil!' : 'Presensi Pulang berhasil!']);
            }

            DB::rollBack();
            return response()->json(['success' => false, 'error' => 'Anda sudah melakukan presensi Masuk dan Pulang hari ini!'], 409);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal Simpan Presensi: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Gagal menyimpan data.'], 500);
        }
    }

    public function formizin()
    {
        $nik = auth('karyawan')->user()->nik;
        $data_izin = Izin::with('masterCuti')->where('nik', $nik)->orderByDesc('tgl_izin_dari')->get();
        return view('karyawan.presensi.izin', compact('data_izin'));
    }
    public function buatizin()
    {
        return view('karyawan.presensi.buatizin');
    }
    public function storeizin(Request $request)
    {
        $nik = auth('karyawan')->user()->nik;
        $tgl_izin_dari = $request->tgl_izin_dari;
        $status = $request->status;
        $keterangan = $request->keterangan;
        if (empty($tgl_izin_dari) || empty($status) || empty($keterangan))
            return redirect('/presensi/izin')->with('error', 'Semua kolom wajib diisi!');
        Izin::create(['nik' => $nik, 'tgl_izin_dari' => $tgl_izin_dari, 'tgl_izin_sampai' => $tgl_izin_dari, 'status' => $status, 'keterangan' => $keterangan, 'status_approved' => 0]);
        return redirect('/presensi/izin')->with('success', 'Data pengajuan berhasil disimpan, menunggu approval.');
    }

    public function histori()
    {
        $namabulan = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
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

        if (empty($bulan) || empty($tahun))
            return view('karyawan.presensi.gethistori', ['histori' => collect([])]);

        $periodEnd = \Carbon\Carbon::create($tahun, $bulan, 25)->endOfDay();
        $periodStart = \Carbon\Carbon::create($tahun, $bulan, 26)->subMonth()->startOfDay();

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
                    $tgl = $date->format("Y-m-d");
                    if ($date >= $periodStart && $date <= $periodEnd) {
                        $existsInPresensi = $histori_presensi->contains(function ($item) use ($tgl) {
                            return date('Y-m-d', strtotime($item->tgl_presensi)) === $tgl;
                        });

                        if (!$existsInPresensi) {
                            $histori_normalized->push((object) [
                                'tgl_presensi' => $tgl, 
                                'jam_in' => '00:00:00', 
                                'jam_out' => '00:00:00', 
                                'foto_in' => '-', 
                                'foto_out' => '-', 
                                'status' => $izin->status, 
                                'keterangan' => $izin->keterangan, 
                                'nama_cuti' => $izin->masterCuti->nama_cuti ?? null, 
                                'jenis' => 'izin'
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
                    $tgl = $date->format("Y-m-d");
                    if ($date >= $periodStart && $date <= $periodEnd) {
                        $existsInPresensi = $histori_presensi->contains(function ($item) use ($tgl) {
                            return date('Y-m-d', strtotime($item->tgl_presensi)) === $tgl;
                        });
                        $existsInNormalized = $histori_normalized->contains('tgl_presensi', $tgl);

                        if (!$existsInPresensi && !$existsInNormalized) {
                            $histori_normalized->push((object) [
                                'tgl_presensi' => $tgl,
                                'jam_in' => '00:00:00',
                                'jam_out' => '00:00:00',
                                'foto_in' => '-',
                                'foto_out' => '-',
                                'status' => 'd',
                                'keterangan' => $dinas->keterangan ?? $dinas->alasan ?? '-',
                                'nama_cuti' => null,
                                'jenis' => 'dinas_luar'
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        $leaderboard = \App\Models\LeaderboardSnapshot::where('nik', $nik)
            ->whereBetween('date', [$periodStart->format('Y-m-d'), $periodEnd->format('Y-m-d')])
            ->get()
            ->groupBy(function ($item) {
                return \Carbon\Carbon::parse($item->date)->format('Y-m-d');
            });

        $today = clone \Carbon\Carbon::today();
        $tmtStr = $karyawan->tmt ? \Carbon\Carbon::parse($karyawan->tmt)->format('Y-m-d') : null;
        $startIterator = ($tmtStr && $tmtStr > $periodStart->format('Y-m-d')) ? \Carbon\Carbon::parse($tmtStr) : clone $periodStart;
        $endIterator = (clone $periodEnd)->isFuture() ? clone $today : clone $periodEnd;

        // Hanya generate status Alpha jika karyawan TIDAK masuk whitelist
        if ((int) ($karyawan->is_whitelist ?? 0) !== 1) {
            while ($startIterator->lte($endIterator)) {
                $tgl = $startIterator->format('Y-m-d');

                $existsInPresensi = $histori_presensi->contains(function ($item) use ($tgl) {
                    return date('Y-m-d', strtotime($item->tgl_presensi)) === $tgl;
                });
                $existsInNormalized = $histori_normalized->contains('tgl_presensi', $tgl);

                if (!$existsInPresensi && !$existsInNormalized) {
                    // Verifikasi Jam Kerja dan Hari Libur
                    $isHariLiburNasional = \App\Models\HariLibur::isHariLibur($tgl, $kode_cabang, $kode_dept);
                    $namaHari = $this->gethari(date('D', strtotime($tgl)));
                    [$jkObj, $isLiburJamKerja, $source] = $this->resolveJamKerja($nik, $kode_dept, $kode_cabang, $namaHari);
                    
                    $isHoliday = $isHariLiburNasional || $isLiburJamKerja || ($namaHari == 'Minggu' && $source == 'none');

                    if (!$isHoliday) {
                        if (!empty($jkObj)) {
                            $jamPulangStr = $jkObj->jam_pulang;
                            $waktuSekarang = \Carbon\Carbon::now();
                            $waktuPulang = \Carbon\Carbon::parse($tgl . ' ' . $jamPulangStr);

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
                            'jenis' => 'alpha'
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
    private $batasLintasHari = "08:00";

    public function gethari($hari)
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

    private function distance($lat1, $lon1, $lat2, $lon2)
    {
        $theta = $lon1 - $lon2;
        $miles = (sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)));
        $miles = acos($miles);
        $miles = rad2deg($miles);
        $miles = $miles * 60 * 1.1515;
        $kilometers = $miles * 1.609344;
        $meters = $kilometers * 1000;
        return compact('meters');
    }

    private function getHariLiburData($tgl_awal, $tgl_akhir, $kode_cabang = null, $kode_dept = null)
    {
        $hariLiburQuery = HariLibur::whereBetween('tanggal_libur', [$tgl_awal, $tgl_akhir]);

        $hariLiburQuery->where(function ($query) use ($kode_cabang, $kode_dept) {
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

            if (!empty($kode_cabang)) {
                $query->orWhere(function ($q) use ($kode_cabang) {
                    $q->where('kode_cabang', $kode_cabang)
                        ->where(function ($d) {
                            $d->whereNull('kode_dept')
                                ->orWhere('kode_dept', '')
                                ->orWhere('kode_dept', 'Semua Departemen');
                        });
                });
            }

            if (!empty($kode_dept)) {
                $query->orWhere(function ($q) use ($kode_dept) {
                    $q->where('kode_dept', $kode_dept)
                        ->where(function ($c) {
                            $c->whereNull('kode_cabang')
                                ->orWhere('kode_cabang', '')
                                ->orWhere('kode_cabang', 'Semua Cabang');
                        });
                });
            }

            if (!empty($kode_cabang) && !empty($kode_dept)) {
                $query->orWhere(function ($q) use ($kode_cabang, $kode_dept) {
                    $q->where('kode_cabang', $kode_cabang)
                        ->where('kode_dept', $kode_dept);
                });
            }
        });

        return $hariLiburQuery->get()
            ->map(function ($item) {
                return date('Y-m-d', strtotime($item->tanggal_libur));
            })
            ->toArray();
    }

    private function resolveJamKerja(string $nik, string $kodeDept, string $kodeCabang, string $hari): array
    {
        $hariNormal = strtolower(trim($hari));

        $setJamKerja = Setjamkerja::with('jamKerja')
            ->where('nik', $nik)
            ->where(DB::raw('LOWER(hari)'), $hariNormal)
            ->first();

        if ($setJamKerja) {
            $isLibur = is_null($setJamKerja->kode_jam_kerja) || $setJamKerja->kode_jam_kerja === 'LIBUR';
            return [$setJamKerja->jamKerja, $isLibur, 'personal'];
        }

        $setJamKerjaDept = KonfigurasiJkDeptDetail::with(['jamKerja', 'konfigurasi'])
            ->where(DB::raw('LOWER(hari)'), $hariNormal)
            ->whereHas('konfigurasi', function ($query) use ($kodeDept, $kodeCabang) {
                $query->where('kode_dept', $kodeDept)->where('kode_cabang', $kodeCabang);
            })->first();

        if ($setJamKerjaDept) {
            $isLibur = is_null($setJamKerjaDept->kode_jam_kerja) || $setJamKerjaDept->kode_jam_kerja === 'LIBUR';
            return [$setJamKerjaDept->jamKerja, $isLibur, 'dept'];
        }

        return [null, false, 'none'];
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

    private function autoCloseLembur($nik, $tgl_presensi, $jam_presensi, $foto_presensi, $jamkerja_shift)
    {
        try {
            $tgl_kemarin = date('Y-m-d', strtotime('-1 days', strtotime($tgl_presensi)));
            $lembur = \App\Models\Lembur::where('nik', $nik)
                ->whereNull('jam_selesai')
                ->whereIn('tanggal_lembur', [$tgl_kemarin, $tgl_presensi])
                ->orderByDesc('tanggal_lembur')
                ->orderByDesc('id')
                ->first();

            if ($lembur) {
                $tanggalLembur = \Carbon\Carbon::parse($lembur->tanggal_lembur)->format('Y-m-d');
                $waktuMulai = \Carbon\Carbon::parse($tanggalLembur . ' ' . $lembur->jam_mulai);
                $jam_selesai = date('H:i', strtotime($jam_presensi));
                $waktuSelesai = \Carbon\Carbon::parse($tgl_presensi . ' ' . $jam_selesai);

                if ($jamkerja_shift) {
                    $karyawan = \App\Models\Karyawan::where('nik', $nik)->first();
                    $isHariLiburNasional = \App\Models\HariLibur::isHariLibur($tgl_presensi, $karyawan->kode_cabang, $karyawan->kode_dept);

                    if (!$isHariLiburNasional) {
                        $waktuShiftMulai = \Carbon\Carbon::parse($tgl_presensi . ' ' . $jamkerja_shift->jam_masuk);
                        if ($waktuMulai->lt($waktuShiftMulai) && $waktuSelesai->gt($waktuShiftMulai)) {
                            $waktuSelesai = $waktuShiftMulai;
                            $jam_selesai = $waktuSelesai->format('H:i');
                        }
                    }
                }

                if ($waktuSelesai->lt($waktuMulai)) {
                    $waktuSelesai->addDay();
                }
                $totalJam = $waktuMulai->floatDiffInHours($waktuSelesai);

                $lembur->update([
                    'jam_selesai' => $jam_selesai,
                    'total_jam' => round($totalJam, 2),
                    'foto_keluar' => $foto_presensi
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AutoCloseLembur Error: ' . $e->getMessage());
        }
    }
}
