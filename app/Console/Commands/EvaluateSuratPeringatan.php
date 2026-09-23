<?php

namespace App\Console\Commands;

use App\Models\HariLibur;
use App\Models\Karyawan;
use App\Models\LeaderboardSnapshot;
use App\Models\SuratPeringatan;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EvaluateSuratPeringatan extends Command
{
    protected $signature = 'sp:evaluate';

    protected $description = 'Evaluasi pelanggaran presensi dan generate SP otomatis';

    public function handle()
    {
        $today = Carbon::today();
        $hariIniTanggal = $today->day;

        // 1. Tentukan Periode Siklus
        $startPeriod = $hariIniTanggal < 26
            ? Carbon::create($today->year, $today->month, 26)->subMonth()
            : Carbon::create($today->year, $today->month, 26);

        $endPeriod = $startPeriod->copy()->addMonthsNoOverflow(1)->day(25);

        $this->info('Memulai Evaluasi SP. Siklus Berjalan: '.$startPeriod->toDateString().' s/d '.$endPeriod->toDateString());

        // =========================================================================
        // BAGIAN A: EVALUASI ALPHA & KETERLAMBATAN (Jalan setiap hari)
        // =========================================================================
        $this->evaluateDailyViolations($startPeriod, $endPeriod, $today);

        // =========================================================================
        // BAGIAN B: EVALUASI POIN MINUS (Hanya jalan di masa tutup buku: tgl 24-25)
        // =========================================================================
        if ($hariIniTanggal >= 24 && $hariIniTanggal <= 25) {
            $this->evaluateMinusPoints($startPeriod, $endPeriod);
        } else {
            $this->info('✅ Sinkronisasi Peringkat selesai. Belum memasuki fase persiapan SP Poin Minus (tgl 24-25).');
        }

        // =========================================================================
        // BAGIAN C: CLEANUP (Nonaktifkan SP double & karyawan yang sudah keluar)
        // =========================================================================
        $this->cleanupDuplicateActiveSP();
        $this->cleanupInactiveKaryawanSP();
    }

    /**
     * Mengevaluasi pelanggaran absen (Alpha) dan Keterlambatan harian
     */
    private function evaluateDailyViolations(Carbon $startPeriod, Carbon $endPeriod, Carbon $today)
    {
        $this->info('🔄 Mengecek pelanggaran Alpha & Keterlambatan...');
        $effectiveDate = Carbon::create(2026, 3, 1);
        $queryStartDate = $effectiveDate->gt($startPeriod) ? $effectiveDate : $startPeriod;

        // Optimasi: Ambil data libur terlebih dahulu (hanya array string tanggal)
        $liburDates = HariLibur::whereBetween('tanggal_libur', [$queryStartDate->toDateString(), $today->toDateString()])
            ->pluck('tanggal_libur')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        // Optimasi: Menggunakan chunk untuk menghindari Out of Memory jika karyawan ratusan/ribuan
        $countSP = 0;
        Karyawan::with([
            'presensis' => fn ($q) => $q->whereBetween('tgl_presensi', [$queryStartDate->toDateString(), $today->toDateString()])->with('jamKerja'),
            'izin' => fn ($q) => $q->where('status_approved', 1)->where('tgl_izin_sampai', '>=', $queryStartDate->toDateString())->where('tgl_izin_dari', '<=', $today->toDateString()),
            'dinasLuars' => fn ($q) => $q->where('status_acc', 'acc')->where('tgl_selesai', '>=', $queryStartDate->toDateString())->where('tgl_mulai', '<=', $today->toDateString()),
        ])->chunk(100, function ($karyawans) use ($queryStartDate, $today, $liburDates, $startPeriod, $endPeriod, &$countSP) {

            $periodCheck = CarbonPeriod::create($queryStartDate, $today->copy()->subDay());
            $siteBranches = array_map('trim', explode(',', get_setting('cabang_tambang', 'CBNG0003,CBNG0011,RBJ,TBKR,CBNG0002')));
            $spTambangAktif = (int) get_setting('sp_tambang_aktif', 0);

            foreach ($karyawans as $k) {
                $nik = $k->nik;

                // Jika karyawan berada di cabang tambang dan fitur SP tambang dinonaktifkan
                if (in_array($k->kode_cabang, $siteBranches) && ! $spTambangAktif) {
                    continue;
                }

                $presensiMap = $k->presensis->keyBy(fn ($p) => Carbon::parse($p->tgl_presensi)->toDateString());

                $absentCount = 0;
                $streakLate = 0;
                $maxStreakLate = 0;

                foreach ($periodCheck as $date) {
                    $dateStr = $date->toDateString();

                    // Lewati hari libur, minggu, atau jika sedang dinas luar
                    if ($date->isSunday() || in_array($dateStr, $liburDates) || $this->isDinasLuar($k, $dateStr)) {
                        $streakLate = 0;

                        continue;
                    }

                    $p = $presensiMap->get($dateStr);
                    $isAlpha = ($p && $p->status == 'a') || (! $p && ! $this->hasIzin($k, $dateStr));

                    if ($isAlpha) {
                        $absentCount++;
                        $streakLate = 0;

                        continue;
                    }

                    // Cek keterlambatan
                    if ($p && $p->jam_in && $p->jamKerja && $p->status == 'h') {
                        if (is_terlambat($p->jamKerja->jam_masuk, $p->jam_in)) {
                            $streakLate++;
                            $maxStreakLate = max($maxStreakLate, $streakLate);
                        } else {
                            $streakLate = 0;
                        }
                    } else {
                        $streakLate = 0;
                    }
                }

                // Tentukan Kandidat SP
                $candidateLevel = 0;
                $violationType = '';
                $reason = '';

                if ($absentCount > 5) {
                    $candidateLevel = 2;
                    $violationType = 'absent';
                    $reason = "Alpha akumulasi > 5x ({$absentCount} kali)";
                } elseif ($maxStreakLate >= 5) {
                    $candidateLevel = 1;
                    $violationType = 'late';
                    $reason = "Terlambat tanpa keterangan {$maxStreakLate} hari berturut-turut";
                } elseif ($absentCount >= 3) {
                    $candidateLevel = 1;
                    $violationType = 'absent';
                    $reason = "Alpha akumulasi >= 3x ({$absentCount} kali)";
                }

                if ($candidateLevel > 0) {
                    if ($this->processSpEscalation($nik, $candidateLevel, $violationType, $reason, $today, null, $startPeriod, $endPeriod)) {
                        $countSP++;
                    }
                }
            }
        });

        $this->info("✅ Evaluasi Harian Selesai. Total SP diterbitkan: {$countSP}");
    }

    /**
     * Mengevaluasi SP khusus dari total poin minus (Jalan tgl 24-25)
     */
    private function evaluateMinusPoints(Carbon $startPeriod, Carbon $endPeriod)
    {
        $this->info('🔄 Fase Tutup Buku: Mengecek Poin Minus untuk persiapan SP 2...');

        $tglAwalStr = $startPeriod->format('Y-m-d');
        $tglAkhirStr = $endPeriod->format('Y-m-d');
        $nextMonthStart = $endPeriod->copy()->addDay();

        // Query aggregasi langsung di level database (sangat efisien)
        // HANYA untuk karyawan yang statusnya Aktif
        $minusedUsers = LeaderboardSnapshot::select('leaderboard_snapshots.nik', 'karyawan.kode_cabang', DB::raw('SUM(leaderboard_snapshots.points) as total_points'))
            ->join('karyawan', 'leaderboard_snapshots.nik', '=', 'karyawan.nik')
            ->where('karyawan.status_aktif', Karyawan::STATUS_AKTIF)
            ->whereNull('karyawan.tanggal_keluar')
            ->whereBetween('leaderboard_snapshots.date', [$tglAwalStr, $tglAkhirStr])
            ->groupBy('leaderboard_snapshots.nik', 'karyawan.kode_cabang')
            ->havingRaw('SUM(leaderboard_snapshots.points) < 0')
            ->get();

        $minusedNiks = $minusedUsers->pluck('nik')->toArray();

        // 1. BATALKAN SP 2 Otomatis yang datanya kembali positif
        $revokedCount = SuratPeringatan::where('violation_type', 'Pelanggaran Disiplin')
            ->where('level', '2')
            ->whereDate('issued_at', $nextMonthStart->format('Y-m-d'))
            ->whereNotIn('nik', $minusedNiks)
            ->delete();

        if ($revokedCount > 0) {
            $this->info("  ♻️ DIBATALKAN: {$revokedCount} SP 2 (Poin telah kembali positif)");
        }

        // 2. GENERATE SP untuk NIK yang Minus
        $countSp = 0;
        $siteBranches = array_map('trim', explode(',', get_setting('cabang_tambang', 'CBNG0003,CBNG0011,RBJ,TBKR,CBNG0002')));
        $spTambangAktif = (int) get_setting('sp_tambang_aktif', 0);

        foreach ($minusedUsers as $user) {
            // Jika karyawan berada di cabang tambang dan fitur SP tambang dinonaktifkan
            if (in_array($user->kode_cabang, $siteBranches) && ! $spTambangAktif) {
                continue;
            }

            $reason = "Akumulasi poin minus presensi harian pada siklus {$tglAwalStr} s/d {$tglAkhirStr} jatuh di angka {$user->total_points} (Minus). Akan dikurangi tunjangan sebanyak 25% Gaji.";

            // Gunakan level 2 sebagai standar Poin Minus
            $candidateLevel = 2;
            $expiresAt = $nextMonthStart->copy()->addMonthsNoOverflow(3)->day(25);

            if ($this->processSpEscalation($user->nik, $candidateLevel, 'Pelanggaran Disiplin', $reason, $nextMonthStart, $expiresAt, $startPeriod, $endPeriod)) {
                $countSp++;
                $this->info("  ⚠️ SP Disiapkan untuk NIK: {$user->nik} dengan total poin {$user->total_points}");
            }
        }

        $this->info("✅ Evaluasi SP Poin Minus Selesai. {$countSp} SP disiapkan.");
    }

    /**
     * Membersihkan duplikasi SP aktif (Jika satu karyawan punya > 1 SP aktif)
     * Hanya akan menyisakan satu SP yang paling baru terbit.
     */
    private function cleanupDuplicateActiveSP()
    {
        $this->info('🔄 Mengecek duplikasi SP aktif...');

        $niksWithMultipleActive = SuratPeringatan::whereDate('expires_at', '>', Carbon::today())
            ->select('nik')
            ->groupBy('nik')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('nik');

        $count = 0;
        foreach ($niksWithMultipleActive as $nik) {
            // Ambil semua SP aktif, urutkan dari level tertinggi dan yang terbaru (issued_at desc)
            $activeSPs = SuratPeringatan::where('nik', $nik)
                ->whereDate('expires_at', '>', Carbon::today())
                ->orderByDesc('level')
                ->orderByDesc('issued_at')
                ->get();

            if ($activeSPs->count() > 1) {
                // Simpan yang pertama (terbaru), nonaktifkan sisanya
                $latest = $activeSPs->shift();
                foreach ($activeSPs as $sp) {
                    $sp->update(['expires_at' => Carbon::yesterday()]);
                    $count++;
                }
            }
        }

        if ($count > 0) {
            $this->info("  ✅ BERHASIL: {$count} duplikasi SP aktif telah dirapikan.");
        }
    }

    /**
     * Menonaktifkan SP untuk karyawan yang sudah keluar atau nonaktif
     */
    private function cleanupInactiveKaryawanSP()
    {
        $this->info('🔄 Membersihkan SP untuk karyawan Nonaktif/Diberhentikan...');

        $inactiveNiks = Karyawan::turnover()->pluck('nik')->toArray();

        $deactivatedCount = SuratPeringatan::whereIn('nik', $inactiveNiks)
            ->whereDate('expires_at', '>', Carbon::today())
            ->update([
                'expires_at' => Carbon::yesterday(),
                'note' => DB::raw("CONCAT(note, ' [NONAKTIF OTOMATIS KARENA KARYAWAN KELUAR]')"),
            ]);

        if ($deactivatedCount > 0) {
            $this->info("  ✅ BERHASIL: {$deactivatedCount} SP dinonaktifkan karena karyawan sudah tidak aktif.");
        }
    }

    // --- Helper Methods ---

    private function hasIzin($karyawan, $dateStr)
    {
        return $karyawan->izin->contains(fn ($i) => $dateStr >= $i->tgl_izin_dari && $dateStr <= $i->tgl_izin_sampai);
    }

    private function isDinasLuar($karyawan, $dateStr)
    {
        return $karyawan->dinasLuars->contains(fn ($d) => $dateStr >= $d->tgl_mulai && $dateStr <= $d->tgl_selesai);
    }

    /**
     * Memproses logika eskalasi (kenaikan level) SP dan menyimpannya.
     * Mengembalikan true jika SP baru berhasil dibuat.
     */
    private function processSpEscalation($nik, $candidateLevel, $violationType, $reason, $issuedAt, $expiresAt = null, $startPeriod = null, $endPeriod = null)
    {
        $today = Carbon::today();
        $issuedAt = Carbon::parse($issuedAt);

        // Cegah duplikasi SP:
        // 1. Cek apakah sudah ada SP dengan tanggal terbit yang SAMA
        $queryExists = SuratPeringatan::where('nik', $nik)
            ->where('violation_type', $violationType);

        $querySameDay = (clone $queryExists)->whereDate('issued_at', $issuedAt->toDateString());
        if ($querySameDay->exists()) {
            return false;
        }

        // 2. Cek apakah sudah pernah terbit SP untuk tipe ini di siklus berjalan
        if ($startPeriod && $endPeriod) {
            $queryExists->whereBetween('issued_at', [$startPeriod->format('Y-m-d'), $endPeriod->format('Y-m-d')]);
        } else {
            $queryExists->whereMonth('issued_at', $issuedAt->month)
                ->whereYear('issued_at', $issuedAt->year);
        }

        if ($queryExists->exists()) {
            return false;
        }

        // Cari SP yang masih aktif saat ini
        $lastActiveSP = SuratPeringatan::where('nik', $nik)
            ->whereDate('expires_at', '>', $today)
            ->orderByDesc('level')
            ->first();

        $finalLevel = $candidateLevel;

        if ($lastActiveSP) {
            // Jika sudah SP3 dan masih melakukan pelanggaran, NONAKTIFKAN AKUN
            if ($lastActiveSP->level == 3) {
                $user = Karyawan::where('nik', $nik)->first();
                if ($user && $user->status_aktif === Karyawan::STATUS_AKTIF) {
                    $user->update(['status_aktif' => Karyawan::STATUS_NONAKTIF]);
                    Log::info("Karyawan {$nik} dinonaktifkan karena mengulangi pelanggaran saat SP3 aktif.");
                    $this->info("Karyawan {$nik} dinonaktifkan karena mengulangi pelanggaran saat SP3 aktif.");
                }

                return false;
            }

            // LOGIKA ESKALASI:
            // 1. Jika SP aktif saat ini BUKAN 'Pelanggaran Disiplin' (Poin Minus), maka bisa eskalasi +1
            // 2. Jika SP aktif saat ini ADALAH 'Pelanggaran Disiplin', maka tidak dihitung eskalasi (level tetap)

            if ($lastActiveSP->violation_type !== 'Pelanggaran Disiplin') {
                $escalatedLevel = min(3, $lastActiveSP->level + 1);
                if ($escalatedLevel > $finalLevel) {
                    $finalLevel = $escalatedLevel;
                    $reason .= ". Eskalasi dari SP{$lastActiveSP->level} (Aktif)";
                }
            } else {
                // Jika dari point minus, kita pastikan minimal levelnya sama dengan yang lama agar tidak downgrade
                $finalLevel = max($finalLevel, (int) $lastActiveSP->level);
            }

            // Deaktivasi SEMUA SP lama yang masih aktif: set berakhir 1 hari sebelum SP baru terbit
            SuratPeringatan::where('nik', $nik)
                ->whereDate('expires_at', '>', $today)
                ->update(['expires_at' => $issuedAt->copy()->subDay()]);
        }

        SuratPeringatan::create([
            'nik' => $nik,
            'level' => $finalLevel,
            'violation_type' => $violationType,
            'issued_at' => $issuedAt,
            'expires_at' => $finalLevel == 3 ? $issuedAt->copy()->addMonthsNoOverflow(1) : ($expiresAt ?? $issuedAt->copy()->addMonths(3)),
            'note' => $reason,
        ]);

        $this->info("SP Created: {$nik} | Level {$finalLevel} | Type: {$violationType}");
        Log::info("Auto SP Created: {$nik} Level {$finalLevel} Type {$violationType}");

        return true;
    }
}
