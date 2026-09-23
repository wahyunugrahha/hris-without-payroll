<?php

namespace App\Console\Commands;

use App\Models\Presensi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupPresensiDuplicates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'presensi:cleanup-duplicates {--date= : Tanggal spesifik (Y-m-d).} {--all : Bersihkan semua data duplikat dari awal project}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up duplicate attendance records created by burst requests';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $isAll = $this->option('all');
        $date = $this->option('date');

        if ($isAll) {
            $this->info('Memulai pembersihan duplikasi presensi untuk SEMUA TANGGAL...');
        } else {
            $date = $date ?: date('Y-m-d');
            $this->info("Memulai pembersihan duplikasi presensi untuk tanggal: {$date}...");
        }

        // Cari grup NIK & Tanggal yang memiliki lebih dari satu data
        $query = Presensi::select('nik', 'tgl_presensi', DB::raw('count(*) as total'))
            ->groupBy('nik', 'tgl_presensi')
            ->havingRaw('count(*) > 1');

        if (! $isAll) {
            $query->where('tgl_presensi', $date);
        }

        $duplicates = $query->get();

        if ($duplicates->isEmpty()) {
            $this->info("Tidak ditemukan data duplikat untuk tanggal {$date}.");

            return 0;
        }

        $totalCleaned = 0;

        foreach ($duplicates as $duplicate) {
            $records = Presensi::with('karyawan')->where('nik', $duplicate->nik)
                ->where('tgl_presensi', $duplicate->tgl_presensi)
                ->orderBy('created_at', 'asc')
                ->get();

            $nama = $records->first() && $records->first()->karyawan ? $records->first()->karyawan->nama_lengkap : 'Tidak Ditemukan';
            $this->comment("Memproses: [{$duplicate->nik}] {$nama} - Tanggal: ".$duplicate->tgl_presensi->format('Y-m-d')." - Ditemukan {$records->count()} data.");

            // LOGIKA SELEKSI PRIORITAS:
            // 1. Prioritaskan record yang sudah ada JAM PULANG (jam_out)
            // 2. Prioritaskan record yang ada FOTO MASUK (foto_in)
            // 3. Ambil yang paling pertama dibuat

            $mainRecord = $records->sort(function ($a, $b) {
                // Cek Kelengkapan Jam Out (Prioritas Utama)
                $aOut = (! empty($a->jam_out) && $a->jam_out !== '00:00:00') ? 1 : 0;
                $bOut = (! empty($b->jam_out) && $b->jam_out !== '00:00:00') ? 1 : 0;

                if ($aOut !== $bOut) {
                    return $bOut <=> $aOut; // 1 (lengkap) di atas 0 (kosong)
                }

                // Cek Foto (Prioritas Kedua)
                $aFoto = (! empty($a->foto_in) && $a->foto_in !== '-') ? 1 : 0;
                $bFoto = (! empty($b->foto_in) && $b->foto_in !== '-') ? 1 : 0;

                if ($aFoto !== $bFoto) {
                    return $bFoto <=> $aFoto;
                }

                // Jika sama, ambil ID terkecil (paling awal)
                return $a->id <=> $b->id;
            })->first();

            $mainRecordId = $mainRecord->id;

            // Hapus semua kecuali yang utama
            $toDelete = $records->where('id', '!=', $mainRecordId)->pluck('id');

            if ($toDelete->isNotEmpty()) {
                $deletedIds = $toDelete->implode(', ');
                Presensi::whereIn('id', $toDelete)->delete();
                $totalCleaned += $toDelete->count();
                $this->warn('   -> Berhasil menghapus '.$toDelete->count().' data duplikat. (IDs: '.$deletedIds.')');
            }
        }

        $this->info("Selesai! Total data duplikat yang dibersihkan: {$totalCleaned}");

        return 0;
    }
}
