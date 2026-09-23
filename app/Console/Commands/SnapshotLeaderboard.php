<?php

namespace App\Console\Commands;

use App\Models\Cabang;
use App\Models\DinasLuar;
use App\Models\HariLibur;
use App\Models\Izin;
use App\Models\JamKerja;
use App\Models\Karyawan;
use App\Models\KonfigurasiJkDeptDetail;
use App\Models\LeaderboardSnapshot;
use App\Models\Presensi;
use App\Models\Setjamkerja;
use App\Models\SuratPeringatan;
use App\Services\JadwalKerjaService;
use App\Support\PeriodeKerja;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SnapshotLeaderboard extends Command
{
    /**
     * Nama command yang akan dipanggil di scheduler.
     */
    protected $signature = 'leaderboard:snapshot';

    protected $description = 'Simpan snapshot poin presensi untuk semua karyawan per cabang';

    public function handle()
    {
        $hariini = date('Y-m-d');
        $hariIniCarbon = Carbon::parse($hariini);

        $periode = PeriodeKerja::dari($hariIniCarbon);
        $tglAwal = $periode->mulai->toMutable();
        $tglAkhir = $periode->selesai->toDateString();

        $datesToProcess = [];
        for ($date = $tglAwal->copy(); $date->lte($hariIniCarbon); $date->addDay()) {
            $datesToProcess[] = $date->format('Y-m-d');
        }

        $this->info('🚀 Memulai proses snapshot Presensi (Siklus Berjalan): '.$tglAwal->format('Y-m-d')." s/d $hariini");

        $cabangs = Cabang::all();

        // 0. PRE-FETCH DATA UNTUK MENJAGA HISTORICAL JADWAL
        $existingSnapshots = LeaderboardSnapshot::whereBetween('date', [$tglAwal->format('Y-m-d'), $hariini])
            ->select('date', 'nik', 'jadwal_masuk', 'jadwal_pulang')
            ->get()
            ->groupBy(function ($item) {
                $d = $item->date instanceof Carbon ? $item->date->format('Y-m-d') : $item->date;

                return $d.'_'.$item->nik;
            });

        // 1. HAPUS SEMUA DATA SNAPSHOT DI PERIODE INI SEKALIGUS
        LeaderboardSnapshot::whereBetween('date', [$tglAwal->format('Y-m-d'), $hariini])->delete();

        // 2. PRE-FETCH DATA
        $allKaryawan = Karyawan::select('nik', 'nama_lengkap', 'kode_cabang', 'kode_dept', 'tmt', 'tanggal_awal_kontrak')
            ->wajibPresensi()
            ->get()
            ->groupBy('kode_cabang');

        $allHariLibur = HariLibur::whereBetween('tanggal_libur', [$tglAwal->format('Y-m-d'), $hariini])->get();

        $allSetJamKerja = Setjamkerja::select('nik', 'hari', 'kode_jam_kerja')->get()->groupBy('nik');

        $allSP = SuratPeringatan::whereDate('expires_at', '>', $tglAwal->format('Y-m-d'))->get()->groupBy('nik');

        $allJamKerja = JamKerja::select('kode_jam_kerja', 'jam_masuk', 'jam_pulang')->get()->keyBy('kode_jam_kerja');

        $allKonfigJkDept = KonfigurasiJkDeptDetail::join('konfigurasi_jk_dept', 'konfigurasi_jk_dept_detail.kode_jk_dept', '=', 'konfigurasi_jk_dept.kode_jk_dept')
            ->select('konfigurasi_jk_dept.kode_cabang', 'konfigurasi_jk_dept.kode_dept', 'konfigurasi_jk_dept_detail.hari', 'konfigurasi_jk_dept_detail.kode_jam_kerja')
            ->get()
            ->groupBy(function ($item) {
                return $item->kode_cabang.'_'.$item->kode_dept.'_'.strtolower($item->hari);
            });

        $allIzin = Izin::where('status', 't')
            ->where('status_approved', 1)
            ->whereDate('tgl_izin_dari', '<=', $hariini)
            ->whereDate('tgl_izin_sampai', '>=', $tglAwal->format('Y-m-d'))
            ->get();

        $allDinasLuar = DinasLuar::where('status_acc', 'acc')
            ->whereDate('tgl_mulai', '<=', $hariini)
            ->whereDate('tgl_selesai', '>=', $tglAwal->format('Y-m-d'))
            ->get();

        foreach ($datesToProcess as $processDate) {
            $this->info('>> Memproses Tanggal: '.$processDate);

            $listIzinHariIni = $allIzin->filter(function ($i) use ($processDate) {
                $mulai = $i->tgl_izin_dari instanceof Carbon ? $i->tgl_izin_dari->format('Y-m-d') : substr($i->tgl_izin_dari, 0, 10);
                $sampai = $i->tgl_izin_sampai instanceof Carbon ? $i->tgl_izin_sampai->format('Y-m-d') : substr($i->tgl_izin_sampai, 0, 10);

                return $mulai <= $processDate && $sampai >= $processDate;
            })->pluck('nik')->toArray();

            $dinasLuars = $allDinasLuar->filter(function ($d) use ($processDate) {
                $mulai = $d->tgl_mulai instanceof Carbon ? $d->tgl_mulai->format('Y-m-d') : substr($d->tgl_mulai, 0, 10);
                $selesai = $d->tgl_selesai instanceof Carbon ? $d->tgl_selesai->format('Y-m-d') : substr($d->tgl_selesai, 0, 10);

                return $mulai <= $processDate && $selesai >= $processDate;
            })->pluck('nik')->toArray();

            $hariIniStr = app(JadwalKerjaService::class)->namaHari(date('D', strtotime($processDate)));

            // 3. AMBIL PRESENSI HARI INI UNTUK SEMUA KARYAWAN
            $presensiHarian = Presensi::select(
                'status',
                'jam_in',
                'jam_out',
                'nik',
                'kode_jam_kerja'
            )
                ->where('tgl_presensi', $processDate)
                ->get()
                ->keyBy('nik');

            $insertDataDay = [];

            // 4. PROSES PER CABANG DARI MEMORI
            foreach ($cabangs as $cabang) {
                $karyawanCabang = $allKaryawan->get($cabang->kode_cabang, collect());

                if ($karyawanCabang->isEmpty()) {
                    continue;
                }

                $calculatedData = [];

                foreach ($karyawanCabang as $karyawan) {
                    $nik = $karyawan->nik;
                    $presensi = $presensiHarian->get($nik);
                    $tglMulaiKerjaObj = $karyawan->tanggal_awal_kontrak ?? $karyawan->tmt;
                    $tglMulaiKerja = $tglMulaiKerjaObj ? $tglMulaiKerjaObj->format('Y-m-d') : null;

                    $jam_in = $presensi->jam_in ?? '00:00:00';
                    $jam_out = $presensi->jam_out ?? '00:00:00';
                    $sortTime = (! empty($jam_in) && $jam_in != '00:00:00') ? $jam_in : '23:59:59';

                    $jamMasukJadwal = null;
                    $jamPulangJadwal = null;

                    $points = 0;

                    if ($tglMulaiKerja && $processDate < $tglMulaiKerja) {
                        continue;
                    } else {
                        $isDinasLuar = in_array($nik, $dinasLuars);
                        $isIzin = in_array($nik, $listIzinHariIni);

                        $isHariLiburNasional = false;
                        foreach ($allHariLibur as $hl) {
                            $tgl_libur_fmt = Carbon::parse($hl->tanggal_libur)->format('Y-m-d');
                            if ($tgl_libur_fmt == $processDate) {
                                // check cabang & dept
                                $kode_cabang = $hl->kode_cabang;
                                $kode_dept = $hl->kode_dept;

                                $c_match = true;
                                if (! empty($kode_cabang) && $kode_cabang != 'Semua Cabang') {
                                    $c_match = in_array($karyawan->kode_cabang, array_map('trim', explode(',', $kode_cabang)));
                                }

                                $d_match = true;
                                if (! empty($kode_dept) && $kode_dept != 'Semua Departemen') {
                                    $d_match = in_array($karyawan->kode_dept, array_map('trim', explode(',', $kode_dept)));
                                }

                                if ($c_match && $d_match) {
                                    $isHariLiburNasional = true;
                                    break;
                                }
                            }
                        }

                        $hariNormal = strtolower($hariIniStr);
                        $setJk = $allSetJamKerja->get($nik)?->first(function ($val) use ($hariNormal) {
                            return strtolower($val->hari) == $hariNormal;
                        });

                        $isLiburJamKerja = false;
                        $kodeJamKerjaAktif = null;

                        if ($setJk) {
                            $isLiburJamKerja = is_null($setJk->kode_jam_kerja) || $setJk->kode_jam_kerja === 'LIBUR';
                            if (! $isLiburJamKerja) {
                                $kodeJamKerjaAktif = $setJk->kode_jam_kerja;
                            }
                        } else {
                            $konfigDept = $allKonfigJkDept->get("{$karyawan->kode_cabang}_{$karyawan->kode_dept}_{$hariNormal}")?->first();
                            if ($konfigDept) {
                                $isLiburJamKerja = is_null($konfigDept->kode_jam_kerja) || $konfigDept->kode_jam_kerja === 'LIBUR';
                                if (! $isLiburJamKerja) {
                                    $kodeJamKerjaAktif = $konfigDept->kode_jam_kerja;
                                }
                            } else {
                                if ($hariIniStr == 'Minggu') {
                                    $isLiburJamKerja = true;
                                }
                            }
                        }

                        if ($presensi && ! empty($presensi->kode_jam_kerja)) {
                            $kodeJamKerjaAktif = $presensi->kode_jam_kerja;
                        }

                        $isHariLiburCuti = $isHariLiburNasional || $isLiburJamKerja;

                        $pointComponents = [];

                        if ($isDinasLuar) {
                            $pointComponents[] = 25;
                        } elseif ($isIzin && is_null($presensi)) {
                            // Jika tidak ada presensi TAPI punya surat izin/cuti/sakit
                            $pointComponents[] = 0;
                        } elseif ($isHariLiburCuti && is_null($presensi)) {
                            // Tidak ada presensi tetapi hari ini adalah hari libur (nasional/hari minggu/libur roster)
                            $pointComponents[] = 0;
                        } elseif (is_null($presensi)) {
                            // Tidak izin, tidak dinas luar, tidak libur, dan tidak absen = ALFA
                            $pointComponents[] = -20;
                        } elseif ($presensi && ($presensi->status === 'h' || $presensi->status === 'x') && ! empty($presensi->jam_in) && $presensi->jam_in != '00:00:00') {
                            // Jika dia Hadir, TAPI terdaftar juga punya surat izin (misal: Izin terlambat masuk / pulang cepat)
                            if ($isIzin) {
                                $pointComponents[] = 5;
                            } else {
                                $cacheKey = $processDate.'_'.$nik;
                                $storedSnapshot = $existingSnapshots->get($cacheKey)?->first();

                                if ($storedSnapshot && ! empty($storedSnapshot->jadwal_masuk) && ! empty($storedSnapshot->jadwal_pulang)) {
                                    $jamMasukJadwal = $storedSnapshot->jadwal_masuk;
                                    $jamPulangJadwal = $storedSnapshot->jadwal_pulang;
                                } else {
                                    $jk = $kodeJamKerjaAktif ? ($allJamKerja->get($kodeJamKerjaAktif) ?? null) : null;
                                    $jamMasukJadwal = $jk?->jam_masuk;
                                    $jamPulangJadwal = $jk?->jam_pulang;
                                }

                                if (! empty($jamMasukJadwal)) {
                                    $jamJadwal = Carbon::parse($processDate.' '.$jamMasukJadwal)->setSeconds(0);
                                    $jamAbsen = Carbon::parse($processDate.' '.$presensi->jam_in)->setSeconds(0);

                                    if ($jamAbsen->lt($jamJadwal)) {
                                        $menitLebihAwal = $jamAbsen->diffInMinutes($jamJadwal);
                                        if ($menitLebihAwal >= 60) {
                                            $pointComponents[] = 35;
                                        } elseif ($menitLebihAwal >= 30) {
                                            $pointComponents[] = 25;
                                        } elseif ($menitLebihAwal >= 15) {
                                            $pointComponents[] = 15;
                                        } else {
                                            $pointComponents[] = (int) max(1, $menitLebihAwal);
                                        }
                                    } elseif ($jamAbsen->gt($jamJadwal)) {
                                        $pointComponents[] = -10;
                                    } else {
                                        $pointComponents[] = 1;
                                    }
                                }
                            }

                            // Lupa absen pulang
                            if (empty($presensi->jam_out) || $presensi->jam_out == '00:00:00') {
                                $pointComponents[] = -5;
                            }
                        } else {
                            // Jika dia ada di tabel presensi, tapi statusnya bukan 'h' (misal: sengaja ditulis 'i', 's', 'c' oleh HR)
                            $pointComponents[] = 0;
                        }

                        $points = array_sum($pointComponents);

                        // 6. OVERRIDE POINT JIKA ADA SP AKTIF
                        $hasActiveSP = $allSP->get($nik)?->contains(function ($sp) use ($processDate) {
                            $tglTerbit = $sp->issued_at instanceof Carbon ? $sp->issued_at->format('Y-m-d') : substr($sp->issued_at, 0, 10);
                            $tglBerakhir = $sp->expires_at instanceof Carbon ? $sp->expires_at->format('Y-m-d') : substr($sp->expires_at, 0, 10);

                            return $processDate >= $tglTerbit && $processDate < $tglBerakhir;
                        });

                        if ($hasActiveSP) {
                            $points = 0;
                            $pointComponents = [0]; // Reset detail agar sinkron
                        }
                    }

                    $calculatedData[] = [
                        'nik' => $nik,
                        'nama_lengkap' => $karyawan->nama_lengkap,
                        'jam_in' => $jam_in,
                        'jam_out' => $jam_out,
                        'jadwal_masuk' => $jamMasukJadwal,
                        'jadwal_pulang' => $jamPulangJadwal,
                        'points' => (int) $points,
                        'point_components' => $pointComponents,
                        'sort_time' => $sortTime,
                    ];
                }

                if (empty($calculatedData)) {
                    continue;
                }

                // 5. SORTING RANKING HARIAN PER CABANG
                usort($calculatedData, function ($a, $b) {
                    if ($a['points'] == $b['points']) {
                        return strcmp($a['sort_time'], $b['sort_time']);
                    }

                    return $b['points'] <=> $a['points'];
                });

                $rank = 1;
                $now = now();
                foreach ($calculatedData as $data) {
                    if (empty($data['nik'])) {
                        continue;
                    }

                    $insertDataDay[] = [
                        'date' => $processDate,
                        'kode_cabang' => $cabang->kode_cabang,
                        'rank' => $rank,
                        'points' => $data['points'],
                        'nik' => $data['nik'],
                        'nama_lengkap' => $data['nama_lengkap'],
                        'jam_in' => $data['jam_in'],
                        'jam_out' => $data['jam_out'],
                        'jadwal_masuk' => $data['jadwal_masuk'],
                        'jadwal_pulang' => $data['jadwal_pulang'],
                        'point_details' => json_encode($data['point_components']),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $rank++;
                }
            }

            if (! empty($insertDataDay)) {
                $chunks = array_chunk($insertDataDay, 1000);
                foreach ($chunks as $chunk) {
                    LeaderboardSnapshot::insert($chunk);
                }
            }
        }
    }
}
