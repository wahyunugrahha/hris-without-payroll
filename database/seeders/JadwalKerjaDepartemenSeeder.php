<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Jadwal kerja default untuk setiap kombinasi cabang x departemen yang BELUM punya jadwal,
 * sehingga karyawan baru langsung bisa absen tanpa perlu diatur manual.
 *
 * Aman dijalankan berulang (juga di production): konfigurasi yang sudah ada tidak pernah diubah.
 * Jadwal khusus (mis. shift tambang) tetap diatur lewat menu Konfigurasi > Jam Kerja Departemen,
 * atau per karyawan lewat Set Jam Kerja.
 *
 *   php artisan db:seed --class=JadwalKerjaDepartemenSeeder
 */
class JadwalKerjaDepartemenSeeder extends Seeder
{
    /** Kode jam kerja per hari; null = libur. Ubah di sini jika jadwal standar perusahaan berbeda. */
    private const JADWAL_DEFAULT = [
        'Senin' => 'JK01',
        'Selasa' => 'JK01',
        'Rabu' => 'JK01',
        'Kamis' => 'JK01',
        'Jumat' => 'JK01',
        'Sabtu' => 'JK04',
        'Minggu' => null,
    ];

    public function run(): void
    {
        $kodeJamKerja = array_filter(array_unique(array_values(self::JADWAL_DEFAULT)));
        $tersedia = DB::table('jam_kerja')->whereIn('kode_jam_kerja', $kodeJamKerja)->pluck('kode_jam_kerja')->all();
        $hilang = array_diff($kodeJamKerja, $tersedia);

        if (! empty($hilang)) {
            $this->command?->error('Jam kerja belum ada: '.implode(', ', $hilang).'. Jalankan JamKerjaSeeder dulu.');

            return;
        }

        $sudahAda = DB::table('konfigurasi_jk_dept')
            ->get(['kode_cabang', 'kode_dept'])
            ->map(fn ($row) => $row->kode_cabang.'|'.$row->kode_dept)
            ->flip();

        $dibuat = 0;

        DB::transaction(function () use ($sudahAda, &$dibuat) {
            foreach (DB::table('cabang')->pluck('kode_cabang') as $kodeCabang) {
                foreach (DB::table('departemen')->pluck('kode_dept') as $kodeDept) {
                    if ($sudahAda->has($kodeCabang.'|'.$kodeDept)) {
                        continue;
                    }

                    // Format kode sama dengan form "Tambah Jam Kerja Departemen".
                    $kodeJkDept = 'J'.$kodeCabang.$kodeDept;

                    DB::table('konfigurasi_jk_dept')->insert([
                        'kode_jk_dept' => $kodeJkDept,
                        'kode_cabang' => $kodeCabang,
                        'kode_dept' => $kodeDept,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('konfigurasi_jk_dept_detail')->insert(
                        collect(self::JADWAL_DEFAULT)
                            ->map(fn ($kode, $hari) => [
                                'kode_jk_dept' => $kodeJkDept,
                                'hari' => $hari,
                                'kode_jam_kerja' => $kode,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ])
                            ->values()
                            ->all()
                    );

                    $dibuat++;
                }
            }
        });

        $this->command?->info("Jadwal default dibuat untuk {$dibuat} kombinasi cabang-departemen (yang sudah ada tidak diubah).");
    }
}
