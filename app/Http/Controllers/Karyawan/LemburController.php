<?php

namespace App\Http\Controllers\Karyawan;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Models\HariLibur;
use App\Models\Lembur;
use App\Models\Presensi;
use App\Services\JadwalKerjaService; // Wajib untuk Transaction
use App\Support\FotoBase64;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LemburController extends Controller
{
    public function __construct(private JadwalKerjaService $jadwalKerja) {}

    public function index(Request $request)
    {
        $nik = auth('karyawan')->user()->nik;

        // Auto-clean draft lama (> 1 hari)
        Lembur::where('nik', $nik)
            ->whereNull('jam_mulai')
            ->where('tanggal_lembur', '<', now()->subDay()->toDateString())
            ->delete();

        // Auto-Sync Lembur dengan Presensi jika ada yang menggantung
        $this->autoSyncWithPresensi($nik);

        $query = Lembur::where('nik', $nik);

        if ($request->filled('bulan')) {
            $query->whereMonth('tanggal_lembur', $request->bulan);
        }
        if ($request->filled('tahun')) {
            $query->whereYear('tanggal_lembur', $request->tahun);
        }

        $lemburs = $query->orderByDesc('tanggal_lembur')->get();

        return view('karyawan.lembur.index', compact('lemburs'));
    }

    public function create()
    {
        return view('karyawan.lembur.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal_lembur' => 'required|date',
            'pekerjaan' => 'required|string|max:255',
            'tempat' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
        ]);

        $nik = auth('karyawan')->user()->nik;
        $karyawan = auth('karyawan')->user();

        // VALIDASI RANGE JAM KERJA
        if ($this->isInsideWorkingHours($karyawan)) {
            return redirect()->back()->with('error', 'Tidak dapat mengajukan lembur selama jam kerja!')->withInput();
        }

        // Cek duplikasi tanggal
        $exist = Lembur::where('nik', $nik)
            ->whereDate('tanggal_lembur', $request->tanggal_lembur)
            ->exists();

        if ($exist) {
            return redirect()->back()->with('error', 'Lembur untuk tanggal ini sudah diajukan.')->withInput();
        }

        $kode_lembur = 'LB'.date('Ymd').rand(1000, 9999);

        try {
            $lembur = Lembur::create([
                'kode_lembur' => $kode_lembur,
                'nik' => $nik,
                'tanggal_lembur' => $request->tanggal_lembur,
                'pekerjaan' => $request->pekerjaan,
                'tempat' => $request->tempat,
                'keterangan' => $request->keterangan,
                'status_approved' => 0,
            ]);

            return redirect()->route('lembur.absenMasuk', $lembur->id)
                ->with('success', 'Pengajuan berhasil. Silakan absen masuk.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $this->failMessage('Gagal.', $e));
        }
    }

    public function edit($id)
    {
        $nik = auth('karyawan')->user()->nik;
        $lembur = Lembur::where('id', $id)->where('nik', $nik)->firstOrFail();

        if ($lembur->status_approved != 0) {
            return redirect()->route('lembur.index')->with('error', 'Data sudah diproses, tidak bisa diedit.');
        }

        if ($errorMsg = $this->cekBatasUpdateLembur($lembur)) {
            return redirect()->route('lembur.index')->with('error', $errorMsg);
        }

        return view('karyawan.lembur.edit', compact('lembur'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'tanggal_lembur' => 'required|date',
            'pekerjaan' => 'required|string|max:255',
            'tempat' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
        ]);

        $nik = auth('karyawan')->user()->nik;
        $lembur = Lembur::where('id', $id)->where('nik', $nik)->firstOrFail();

        if ($lembur->status_approved != 0) {
            return redirect()->route('lembur.index')->with('error', 'Data sudah diproses.');
        }

        if ($errorMsg = $this->cekBatasUpdateLembur($lembur)) {
            return redirect()->route('lembur.index')->with('error', $errorMsg);
        }

        $lembur->update($request->only(['tanggal_lembur', 'pekerjaan', 'tempat', 'keterangan']));

        return redirect()->route('lembur.index')->with('success', 'Data diperbarui.');
    }

    public function destroy($id)
    {
        $nik = auth('karyawan')->user()->nik;
        $lembur = Lembur::where('id', $id)->where('nik', $nik)->firstOrFail();

        if ($lembur->status_approved != 0) {
            return redirect()->route('lembur.index')->with('error', 'Data sudah diproses.');
        }

        $this->deleteFile($lembur->foto_masuk);
        $this->deleteFile($lembur->foto_keluar);
        $lembur->delete();

        return redirect()->route('lembur.index')->with('success', 'Data dihapus.');
    }

    public function cancelDraft($id)
    {
        $nik = auth('karyawan')->user()->nik;
        $lembur = Lembur::where('id', $id)->where('nik', $nik)->first();

        if (! $lembur) {
            return response()->json(['status' => 'not_found'], 404);
        }
        if (! empty($lembur->jam_mulai)) {
            return response()->json(['status' => 'already_started'], 409);
        }

        $this->deleteFile($lembur->foto_masuk);
        $this->deleteFile($lembur->foto_keluar);
        $lembur->delete();

        return response()->json(['status' => 'deleted']);
    }

    public function absenMasuk($id)
    {
        $nik = auth('karyawan')->user()->nik;
        $lembur = Lembur::where('id', $id)->where('nik', $nik)->firstOrFail();

        if (! empty($lembur->jam_mulai)) {
            return redirect()->route('lembur.index')->with('info', 'Anda sudah absen masuk.');
        }

        return view('karyawan.lembur.absen-masuk', compact('lembur'));
    }

    public function storeAbsenMasuk(Request $request, $id)
    {
        $request->validate(['image' => 'required']);
        $nik = auth('karyawan')->user()->nik;
        $karyawan = auth('karyawan')->user();
        $lembur = Lembur::where('id', $id)->where('nik', $nik)->firstOrFail();

        // VALIDASI RANGE JAM KERJA
        if ($this->isInsideWorkingHours($karyawan)) {
            return redirect()->back()->with('error', 'Tidak dapat melakukan absen lembur selama jam kerja!')->withInput();
        }

        $fileName = null;

        DB::beginTransaction(); // Start Transaction
        try {
            $fileName = $this->processImage($request->image, $lembur->kode_lembur.'_masuk');
            $jam_mulai = now()->format('H:i');

            $lembur->update([
                'jam_mulai' => $jam_mulai,
                'foto_masuk' => $fileName,
            ]);

            DB::commit(); // Save Permanent

            return redirect()->route('lembur.index')->with('success', 'Absen masuk berhasil.');

        } catch (\Exception $e) {
            DB::rollBack(); // Batalkan DB
            $this->deleteFile($fileName); // Hapus file yang terlanjur terupload

            return redirect()->back()->with('error', $this->failMessage('Gagal memproses data.', $e));
        }
    }

    public function absenKeluar($id)
    {
        $karyawan = auth('karyawan')->user();
        $nik = $karyawan->nik;

        $lembur = Lembur::where('id', $id)->where('nik', $nik)->firstOrFail();

        if (empty($lembur->jam_mulai)) {
            return redirect()->route('lembur.absenMasuk', $id)->with('error', 'Silakan absen masuk dulu.');
        }

        // Coba sinkronisasi otomatis menggunakan Presensi Masuk (Jika belum absen keluar lembur)
        if (empty($lembur->jam_selesai)) {
            $tanggalLembur = Carbon::parse($lembur->tanggal_lembur)->format('Y-m-d');
            $tglBesok = Carbon::parse($tanggalLembur)->addDay()->format('Y-m-d');
            $waktuMulai = Carbon::parse($tanggalLembur.' '.$lembur->jam_mulai);

            $presensiList = Presensi::where('nik', $nik)
                ->whereIn('tgl_presensi', [$tanggalLembur, $tglBesok])
                ->whereNotNull('jam_in')
                ->where('jam_in', '!=', '00:00:00') // Pastikan ada data riil
                ->orderBy('tgl_presensi')
                ->orderBy('jam_in')
                ->get();

            // Cari presensi masuk yang benar-benar TERJADI SETELAH jam mulai lembur
            $presensi = $presensiList->first(function ($p) use ($waktuMulai) {
                $tglStr = Carbon::parse($p->tgl_presensi)->format('Y-m-d');
                $waktuPresensi = Carbon::parse($tglStr.' '.$p->jam_in);

                return $waktuPresensi->gt($waktuMulai);
            });

            if ($presensi) {
                $tglPresensi = Carbon::parse($presensi->tgl_presensi)->format('Y-m-d');
                $jam_selesai = date('H:i', strtotime($presensi->jam_in));
                $waktuSelesai = Carbon::parse($tglPresensi.' '.$jam_selesai);

                $dayNamePresensi = $this->jadwalKerja->namaHari(date('D', strtotime($tglPresensi)));
                [$jamkerja, $isLibur] = $this->jadwalKerja->untukHari($nik, $karyawan->kode_dept, $karyawan->kode_cabang, $dayNamePresensi);
                $isHariLiburNasional = HariLibur::isHariLibur($tglPresensi, $karyawan->kode_cabang, $karyawan->kode_dept);

                if ($jamkerja && ! $isLibur && ! $isHariLiburNasional) {
                    $waktuShiftMulai = Carbon::parse($tglPresensi.' '.$jamkerja->jam_masuk);
                    // Stop otomatis di jam masuk jadwal jika telat masuk reguler
                    if ($waktuMulai->lt($waktuShiftMulai) && $waktuSelesai->gte($waktuShiftMulai)) {
                        $waktuSelesai = $waktuShiftMulai;
                        $jam_selesai = $waktuSelesai->format('H:i');
                    }
                }

                if ($waktuSelesai->lt($waktuMulai)) {
                    $waktuSelesai->addDay(); // Lintas hari
                }
                $totalJam = $waktuMulai->floatDiffInHours($waktuSelesai);

                $lembur->update([
                    'jam_selesai' => $jam_selesai,
                    'total_jam' => round($totalJam, 2),
                    'foto_keluar' => $presensi->foto_in,
                    'keterangan' => $lembur->keterangan ? $lembur->keterangan.' (Auto-synced by Shift Check-in)' : '(Auto-synced by Shift Check-in)',
                ]);

                return redirect()->route('lembur.index')->with('success', 'Lembur otomatis ditutup menggunakan bukti absen masuk shift Anda.');
            }
        }

        if ($errorMsg = $this->cekBatasUpdateLembur($lembur)) {
            // Jika batas update habis dan belum absen keluar, auto-close agar modal tidak looping
            if (empty($lembur->jam_selesai)) {
                $hariIni = $this->jadwalKerja->namaHari(date('D'));
                [$jamkerjaA, $isLiburA] = $this->jadwalKerja->untukHari($nik, $karyawan->kode_dept, $karyawan->kode_cabang, $hariIni);

                // Secara default, jika tidak ditemukan jadwal, gunakan jam mulai (0 jam)
                $jam_selesai_fallback = date('H:i', strtotime($lembur->jam_mulai));
                $waktuSelesai_fb = Carbon::parse($lembur->tanggal_lembur.' '.$jam_selesai_fallback);
                $waktuMulai_fb = Carbon::parse($lembur->tanggal_lembur.' '.$lembur->jam_mulai);

                // Jika ada jam kerja hari ini dan bukan hari libur, gunakan jam_masuk sebagai jam selesai lembur
                $isHariLiburNasionalA = HariLibur::isHariLibur(date('Y-m-d'), $karyawan->kode_cabang, $karyawan->kode_dept);
                if ($jamkerjaA && ! $isLiburA && ! $isHariLiburNasionalA) {
                    $jam_selesai_fallback = date('H:i', strtotime($jamkerjaA->jam_masuk));
                    $waktuSelesai_fb = Carbon::today()->setTimeFromTimeString($jam_selesai_fallback);

                    if ($waktuSelesai_fb->lt($waktuMulai_fb)) {
                        $waktuSelesai_fb->addDay();
                    }
                }

                $totalJam_fb = $waktuMulai_fb->floatDiffInHours($waktuSelesai_fb);

                $lembur->update([
                    'jam_selesai' => $jam_selesai_fallback,
                    'total_jam' => round($totalJam_fb, 2),
                    'keterangan' => $lembur->keterangan ? $lembur->keterangan.' (Auto-closed: Expired)' : '(Auto-closed: Expired)',
                ]);

                return redirect()->route('lembur.index')->with('success', 'Waktu absen lembur telah habis. Lembur ditutup otomatis menggunakan jam masuk shift Anda.');
            }

            return redirect()->route('lembur.index')->with('error', $errorMsg);
        }

        // Fitur perpanjang: Tetap izinkan masuk ke halaman absen keluar jika status belum disetujui

        return view('karyawan.lembur.absen-keluar', compact('lembur'));
    }

    public function storeAbsenKeluar(Request $request, $id)
    {
        $request->validate(['image' => 'required']);
        $nik = auth('karyawan')->user()->nik;
        $lembur = Lembur::where('id', $id)->where('nik', $nik)->firstOrFail();

        if ($errorMsg = $this->cekBatasUpdateLembur($lembur)) {
            return redirect()->route('lembur.index')->with('error', $errorMsg);
        }

        $fileName = null;

        DB::beginTransaction(); // Start Transaction
        try {
            $fileName = $this->processImage($request->image, $lembur->kode_lembur.'_keluar');
            $jam_selesai = now()->format('H:i');

            // PostgreSQL Safe: Format tanggal murni Y-m-d
            $tanggal = Carbon::parse($lembur->tanggal_lembur)->format('Y-m-d');
            $waktuMulai = Carbon::parse($tanggal.' '.$lembur->jam_mulai);
            $waktuSelesai = Carbon::parse($tanggal.' '.$jam_selesai);

            // STOP OTOMATIS JIKA OVERLAP KE JAM KERJA
            $karyawan = auth('karyawan')->user();
            [$jamkerja, $isLibur] = $this->jadwalKerja->untukHari($karyawan->nik, $karyawan->kode_dept, $karyawan->kode_cabang, $this->jadwalKerja->namaHari(date('D')));
            $isHariLiburNasional = HariLibur::isHariLibur($tanggal, $karyawan->kode_cabang, $karyawan->kode_dept);

            if ($jamkerja && ! $isLibur && ! $isHariLiburNasional) {
                $waktuShiftMulai = Carbon::parse($tanggal.' '.$jamkerja->jam_masuk);
                $waktuShiftSelesai = Carbon::parse($tanggal.' '.$jamkerja->jam_pulang);

                // Jika shift lintas hari
                if ($jamkerja->lintashari == 1 && $waktuShiftSelesai->lt($waktuShiftMulai)) {
                    $waktuShiftSelesai->addDay();
                }

                // Jika lembur mulai sebelum shift dan selesai saat atau setelah shift mulai
                if ($waktuMulai->lt($waktuShiftMulai) && $waktuSelesai->gte($waktuShiftMulai)) {
                    $waktuSelesai = $waktuShiftMulai; // Truncate ke jam mulai shift
                    $jam_selesai = $waktuSelesai->format('H:i');
                }
            }

            // Hapus foto keluar lama jika ada (untuk fitur edit/perpanjang)
            if (! empty($lembur->foto_keluar)) {
                $this->deleteFile($lembur->foto_keluar);
            }

            // Handle Lintas Hari
            if ($waktuSelesai->lt($waktuMulai)) {
                $waktuSelesai->addDay();
            }

            $totalJam = $waktuMulai->floatDiffInHours($waktuSelesai);

            $updateData = [
                'jam_selesai' => $jam_selesai,
                'total_jam' => round($totalJam, 2),
                'foto_keluar' => $fileName,
            ];

            // Simpan metadata saat jam selesai diperbarui
            if (! empty($lembur->jam_selesai)) {
                if (empty($lembur->jam_selesai_awal)) {
                    $updateData['jam_selesai_awal'] = $lembur->jam_selesai;
                }
                $updateData['update_count'] = $lembur->update_count + 1;
                $updateData['last_update_at'] = now();
            }

            $lembur->update($updateData);

            DB::commit(); // Save Permanent

            return redirect()->route('lembur.index')
                ->with('success', 'Absen keluar berhasil.')
                ->with('show_detail', $lembur->id);

        } catch (\Exception $e) {
            DB::rollBack(); // Batalkan DB
            $this->deleteFile($fileName); // Hapus file yang terlanjur terupload

            return redirect()->back()->with('error', $this->failMessage('Gagal memproses data.', $e));
        }
    }

    // --- Helper Functions ---

    private function processImage($base64_string, $prefix)
    {
        $image_base64 = FotoBase64::decode($base64_string);
        if ($image_base64 === null) {
            throw new BusinessException('Foto absen tidak valid. Silakan ambil ulang foto.');
        }

        $fileName = $prefix.'.jpeg'; // Paksa ekstensi JPEG
        Storage::disk('public')->put('uploads/absensi/'.$fileName, $image_base64);

        return $fileName;
    }

    private function deleteFile($filename)
    {
        if ($filename && Storage::disk('public')->exists('uploads/absensi/'.$filename)) {
            Storage::disk('public')->delete('uploads/absensi/'.$filename);
        }
    }

    // --- Helper Functions Untuk Validasi Jam Kerja ---

    private function cekBatasUpdateLembur($lembur)
    {
        $karyawan = auth('karyawan')->user();
        $tanggal_lembur = $lembur->tanggal_lembur;
        $today = date('Y-m-d');
        $now = Carbon::now();

        if ($today > $tanggal_lembur) {
            $namaHariLembur = $this->jadwalKerja->namaHari(date('D', strtotime($tanggal_lembur)));
            [$jamKerjaLembur, $isLiburLembur] = $this->jadwalKerja->untukHari($karyawan->nik, $karyawan->kode_dept, $karyawan->kode_cabang, $namaHariLembur);

            $isLintasHari = ($jamKerjaLembur && $jamKerjaLembur->lintashari == 1);

            if (! $isLintasHari) {
                return 'Tidak dapat diproses: Lembur tidak bisa diperbarui pada hari yang berbeda.';
            } else {
                $diffDays = Carbon::parse($tanggal_lembur)->startOfDay()->diffInDays(Carbon::parse($today)->startOfDay());
                if ($diffDays > 1) {
                    return 'Tidak dapat diproses: Batas waktu perpanjangan lembur lintas hari maksimal H+1.';
                }

                $namaHariIni = $this->jadwalKerja->namaHari(date('D'));
                [$jamKerjaHariIni, $isLiburHariIni] = $this->jadwalKerja->untukHari($karyawan->nik, $karyawan->kode_dept, $karyawan->kode_cabang, $namaHariIni);

                if ($jamKerjaHariIni && ! $isLiburHariIni) {
                    $batasUpdate = Carbon::parse($today.' '.$jamKerjaHariIni->jam_masuk);
                    if ($now->gte($batasUpdate)) {
                        return 'Tidak dapat diproses: Sudah melewati batas jam masuk shift berikutnya.';
                    }
                }
            }
        }

        return null;
    }

    private function isInsideWorkingHours($karyawan)
    {
        $today = date('Y-m-d');
        $now = date('H:i');
        $dayName = $this->jadwalKerja->namaHari(date('D'));

        // Jika hari libur nasional, anggap tidak di dalam jam kerja regular
        if (HariLibur::isHariLibur($today, $karyawan->kode_cabang, $karyawan->kode_dept)) {
            return false;
        }

        [$jamkerja, $isLibur] = $this->jadwalKerja->untukHari($karyawan->nik, $karyawan->kode_dept, $karyawan->kode_cabang, $dayName);

        // Jika tidak ada jadwal atau sedang libur jadwal
        if (! $jamkerja || $isLibur) {
            return false;
        }

        $waktuMasuk = $jamkerja->jam_masuk;
        $waktuPulang = $jamkerja->jam_pulang;

        if ($jamkerja->lintashari == 1) {
            // Logika lintas hari: jika jam sekarang >= masuk OR jam sekarang <= pulang (pagi buta)
            if ($now >= $waktuMasuk || $now <= $waktuPulang) {
                return true;
            }
        } else {
            // Normal: jika di antara masuk dan pulang
            if ($now >= $waktuMasuk && $now <= $waktuPulang) {
                return true;
            }
        }

        return false;
    }

    private function autoSyncWithPresensi($nik)
    {
        try {
            $openLemburs = Lembur::where('nik', $nik)->whereNull('jam_selesai')->whereNotNull('jam_mulai')->get();
            if ($openLemburs->isEmpty()) {
                return;
            }

            foreach ($openLemburs as $lembur) {
                $tanggalLembur = Carbon::parse($lembur->tanggal_lembur)->format('Y-m-d');
                $tgl_besok = Carbon::parse($tanggalLembur)->addDay()->format('Y-m-d');

                $presensi = Presensi::where('nik', $nik)
                    ->whereIn('tgl_presensi', [$tanggalLembur, $tgl_besok])
                    ->whereNotNull('jam_in')
                    ->orderBy('tgl_presensi')
                    ->orderBy('jam_in')
                    ->first();

                if ($presensi) {
                    $waktuMulai = Carbon::parse($tanggalLembur.' '.$lembur->jam_mulai);
                    $tglPresensi = Carbon::parse($presensi->tgl_presensi)->format('Y-m-d');
                    $jam_selesai = date('H:i', strtotime($presensi->jam_in));
                    $waktuSelesai = Carbon::parse($tglPresensi.' '.$jam_selesai);

                    if ($waktuSelesai->lte($waktuMulai)) {
                        continue;
                    }

                    $dayNamePresensi = $this->jadwalKerja->namaHari(date('D', strtotime($tglPresensi)));
                    $karyawan = $lembur->karyawan;
                    [$jamkerja, $isLibur] = $this->jadwalKerja->untukHari($nik, $karyawan->kode_dept, $karyawan->kode_cabang, $dayNamePresensi);
                    $isHariLiburNasional = HariLibur::isHariLibur($tglPresensi, $karyawan->kode_cabang, $karyawan->kode_dept);

                    if ($jamkerja && ! $isLibur && ! $isHariLiburNasional) {
                        $waktuShiftMulai = Carbon::parse($tglPresensi.' '.$jamkerja->jam_masuk);
                        if ($waktuMulai->lt($waktuShiftMulai) && $waktuSelesai->gt($waktuShiftMulai)) {
                            $waktuSelesai = $waktuShiftMulai;
                            $jam_selesai = $waktuSelesai->format('H:i');
                        }
                    }

                    if ($waktuSelesai->lt($waktuMulai)) {
                        $waktuSelesai->addDay();
                    }
                    $totalJam = $waktuMulai->floatDiffInHours($waktuSelesai);

                    $lembur->update([
                        'jam_selesai' => $jam_selesai,
                        'total_jam' => round($totalJam, 2),
                        'foto_keluar' => $presensi->foto_in,
                        'keterangan' => $lembur->keterangan.' (Auto-synced)',
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('AutoSync Error: '.$e->getMessage());
        }
    }
}
