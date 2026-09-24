<?php

namespace Database\Seeders;

use App\Models\Jabatan;
use App\Models\Karyawan;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Data dummy untuk mencoba tampilan & fitur (bukan untuk production).
 *
 *   php artisan db:seed --class=DummyDataSeeder
 *
 * Semua karyawan dummy ber-NIK 9000xx; menjalankan ulang seeder menghapus
 * data dummy lama lebih dulu, jadi aman dipanggil berkali-kali.
 * Butuh data master dari DatabaseSeeder (cabang, departemen, jabatan, jam kerja).
 */
class DummyDataSeeder extends Seeder
{
    private const PREFIX_NIK = '9000';

    private const JUMLAH_KARYAWAN = 30;

    private const HARI_PRESENSI = 30;

    private const KODE_JAM_KERJA = 'JK01';

    /** Cabang => departemen yang dipakai. */
    private const PENEMPATAN = [
        'CBNG0001' => ['MKN', 'OPS', 'GDG'],
        'CBNG0002' => ['OPS', 'MKN'],
        'HOJKT01' => ['HRD', 'DEV', 'ADM'],
    ];

    /** Nama jabatan => bobot kemunculan. */
    private const JABATAN = [
        'Staff' => 8,
        'Crew Mekanik' => 6,
        'Staff HR' => 2,
        'Supervisor' => 2,
        'Inventory Auditor' => 1,
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->error('DummyDataSeeder tidak boleh dijalankan di production.');

            return;
        }

        $faker = Factory::create('id_ID');
        $faker->seed(20260923);

        DB::transaction(function () use ($faker) {
            $this->hapusDataLama();

            $karyawan = $this->buatKaryawan($faker);
            $aktif = $karyawan->where('status_aktif', Karyawan::STATUS_AKTIF);

            $tanggalIzin = $this->buatIzin($faker, $aktif);
            $this->buatPresensi($faker, $aktif, $tanggalIzin);
            $this->buatLembur($faker, $aktif);
            $this->buatDinasLuar($faker, $aktif);
            $this->buatSuratPeringatan($faker, $aktif);
            $this->buatKpiHarian($aktif);
        });

        $this->command->info('Data dummy dibuat: '.self::JUMLAH_KARYAWAN.' karyawan (NIK '.self::PREFIX_NIK.'xx), password 123456.');
    }

    private function hapusDataLama(): void
    {
        $nik = fn ($q) => $q->where('nik', 'like', self::PREFIX_NIK.'%');

        $kpiIds = DB::table('kpi_daily')->where($nik)->pluck('id');
        DB::table('kpi_daily_detail')->whereIn('kpi_daily_id', $kpiIds)->delete();
        DB::table('kpi_daily_extra')->whereIn('kpi_daily_id', $kpiIds)->delete();

        foreach (['kpi_daily', 'presensi', 'izin', 'lembur', 'dinas_luar', 'surat_peringatan'] as $tabel) {
            DB::table($tabel)->where($nik)->delete();
        }

        DB::table('model_has_roles')
            ->where('model_type', Karyawan::class)
            ->where('model_id', 'like', self::PREFIX_NIK.'%')
            ->delete();
        DB::table('karyawan')->where($nik)->delete();
    }

    private function buatKaryawan($faker)
    {
        $jabatan = Jabatan::with('role')->whereIn('nama_jabatan', array_keys(self::JABATAN))->get()->keyBy('nama_jabatan');
        $poolJabatan = collect(self::JABATAN)
            ->flatMap(fn ($bobot, $nama) => array_fill(0, $bobot, $nama))
            ->filter(fn ($nama) => $jabatan->has($nama))
            ->values();
        $password = Hash::make('123456');
        $hasil = collect();

        for ($i = 1; $i <= self::JUMLAH_KARYAWAN; $i++) {
            $cabang = array_rand(self::PENEMPATAN);
            $gender = $faker->randomElement(['male', 'female']);
            $masuk = Carbon::today()->subDays($faker->numberBetween(20, 1400));

            // Variasi status untuk widget dashboard: baru masuk, kontrak hampir habis, sudah keluar.
            $habisKontrak = $masuk->copy()->addYears(2);
            $status = Karyawan::STATUS_AKTIF;
            $keluar = null;
            if ($i <= 3) {
                $masuk = Carbon::today()->subDays($faker->numberBetween(5, 80));
                $habisKontrak = $masuk->copy()->addYear();
            } elseif ($i <= 6) {
                $habisKontrak = Carbon::today()->addDays($faker->numberBetween(3, 45));
            } elseif ($i >= self::JUMLAH_KARYAWAN - 1) {
                $status = Karyawan::STATUS_NONAKTIF;
                $keluar = Carbon::today()->subDays($faker->numberBetween(10, 60));
            }

            $namaJabatan = $poolJabatan->random();
            $data = [
                'nik' => self::PREFIX_NIK.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'nama_lengkap' => $faker->name($gender),
                'no_hp' => '08'.$faker->numerify('##########'),
                'password' => $password,
                'jenis_kelamin' => $gender === 'male' ? 'L' : 'P',
                'tempat_lahir' => $faker->city(),
                'tanggal_lahir' => $faker->dateTimeBetween('-50 years', '-20 years')->format('Y-m-d'),
                'alamat' => $faker->address(),
                'email' => $faker->unique()->safeEmail(),
                'pendidikan_terakhir' => $faker->randomElement(['SMA', 'D3', 'S1', 'S1', 'S2']),
                'kode_cabang' => $cabang,
                'kode_dept' => $faker->randomElement(self::PENEMPATAN[$cabang]),
                'jabatan_id' => $jabatan[$namaJabatan]->id,
                'status_karyawan' => $faker->randomElement(['PKWT', 'PKWT', 'PKWTT']),
                'tanggal_awal_kontrak' => $masuk->toDateString(),
                'tmt' => $masuk->toDateString(),
                'tanggal_habis_kontrak' => $habisKontrak->toDateString(),
                'status_aktif' => $status,
                'tanggal_keluar' => $keluar?->toDateString(),
            ];
            $data['nama_panggilan'] = explode(' ', $data['nama_lengkap'])[0];

            $karyawan = Karyawan::create($data);
            if ($role = $jabatan[$namaJabatan]->role) {
                $karyawan->syncRoles([$role->name]);
            }
            $hasil->push($karyawan);
        }

        return $hasil;
    }

    /**
     * @return array<string, array<string, true>> nik => [tanggal => true] yang tertutup izin disetujui
     */
    private function buatIzin($faker, $karyawan): array
    {
        $tertutup = [];
        $urut = [];

        foreach ($karyawan->random(min(14, $karyawan->count())) as $k) {
            $jenis = $faker->randomElement(['i', 's', 's', 'c']);
            $dari = Carbon::today()->subDays($faker->numberBetween(-5, self::HARI_PRESENSI));
            $sampai = $dari->copy()->addDays($jenis === 'c' ? $faker->numberBetween(1, 3) : $faker->numberBetween(0, 1));
            $statusApproval = $faker->randomElement(['0', '1', '1', '2']);

            $format = 'IZ'.$dari->format('m').$dari->format('y');
            $urut[$format] = ($urut[$format] ?? 900) + 1;

            DB::table('izin')->insert([
                'kode_izin' => $format.str_pad((string) $urut[$format], 4, '0', STR_PAD_LEFT),
                'nik' => $k->nik,
                'tgl_izin_dari' => $dari->toDateString(),
                'tgl_izin_sampai' => $sampai->toDateString(),
                'status' => $jenis,
                'kode_cuti' => $jenis === 'c' ? 'CTH' : null,
                'keterangan' => match ($jenis) {
                    'i' => $faker->randomElement(['Keperluan keluarga', 'Mengurus dokumen', 'Acara pernikahan saudara']),
                    's' => $faker->randomElement(['Demam', 'Sakit gigi', 'Kontrol dokter']),
                    default => 'Cuti tahunan',
                },
                'status_approved' => $statusApproval,
                'catatan_ditolak' => $statusApproval === '2' ? 'Kebutuhan operasional sedang tinggi.' : null,
                'status_decided_at' => $statusApproval === '0' ? null : $dari->copy()->subDay(),
                'created_at' => $dari->copy()->subDays(2),
                'updated_at' => now(),
            ]);

            if ($statusApproval === '1') {
                // Sama seperti IzinApprovalService: izin disetujui tercatat sebagai presensi i/s/c.
                foreach (CarbonPeriod::create($dari, $sampai) as $tgl) {
                    $tertutup[$k->nik][$tgl->toDateString()] = true;
                    if (! $tgl->isSunday()) {
                        DB::table('presensi')->insert([
                            'nik' => $k->nik,
                            'tgl_presensi' => $tgl->toDateString(),
                            'kode_jam_kerja' => self::KODE_JAM_KERJA,
                            'foto_in' => '-',
                            'foto_out' => '-',
                            'lokasi_in' => '-',
                            'lokasi_out' => '-',
                            'status' => $jenis,
                            'jam_in' => '00:00:00',
                            'jam_out' => '00:00:00',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        return $tertutup;
    }

    private function buatPresensi($faker, $karyawan, array $tanggalIzin): void
    {
        $lokasi = DB::table('cabang')->pluck('lokasi_kantor', 'kode_cabang');
        $baris = [];

        foreach (CarbonPeriod::create(Carbon::today()->subDays(self::HARI_PRESENSI), Carbon::today()) as $tgl) {
            if ($tgl->isSunday()) { // Sabtu hari kerja (JK04) sesuai jadwal departemen
                continue;
            }
            $hariIni = $tgl->isToday();

            foreach ($karyawan as $k) {
                $tanggal = $tgl->toDateString();
                if (isset($tanggalIzin[$k->nik][$tanggal]) || $faker->boolean(8)) {
                    continue; // izin disetujui atau tidak absen (alpha)
                }

                // Sebagian besar datang sebelum jam masuk (09:10), sebagian terlambat.
                $jamIn = $faker->boolean(82)
                    ? $tgl->copy()->setTime(7, 30)->addMinutes($faker->numberBetween(0, 95))
                    : $tgl->copy()->setTime(9, 11)->addMinutes($faker->numberBetween(0, 70));
                $sudahPulang = ! $hariIni || $faker->boolean(20);
                $koordinat = $this->geser($faker, (string) ($lokasi[$k->kode_cabang] ?? '0,0'));
                $janggal = $faker->boolean(4);

                $baris[] = [
                    'nik' => $k->nik,
                    'tgl_presensi' => $tanggal,
                    'jam_in' => $jamIn->format('H:i:s'),
                    'foto_in' => $k->nik.'_'.$tanggal.'_in.png',
                    'lokasi_in' => $janggal ? $this->bulatkan($koordinat) : $koordinat,
                    'jam_out' => $sudahPulang ? $tgl->copy()->setTime(17, 0)->addMinutes($faker->numberBetween(0, 90))->format('H:i:s') : null,
                    'foto_out' => $sudahPulang ? $k->nik.'_'.$tanggal.'_out.png' : null,
                    'lokasi_out' => $sudahPulang ? $this->geser($faker, $koordinat) : null,
                    'kode_jam_kerja' => self::KODE_JAM_KERJA,
                    'status' => 'h',
                    'kejanggalan' => $janggal ? 'Masuk: koordinat terlalu bulat' : null,
                    'created_at' => $jamIn,
                    'updated_at' => $jamIn,
                ];
            }
        }

        foreach (array_chunk($baris, 500) as $potong) {
            DB::table('presensi')->insert($potong);
        }
    }

    private function buatLembur($faker, $karyawan): void
    {
        foreach ($karyawan->random(min(10, $karyawan->count())) as $k) {
            $tgl = Carbon::today()->subDays($faker->numberBetween(1, self::HARI_PRESENSI));
            $mulai = $tgl->copy()->setTime(17, 30);
            $selesai = $mulai->copy()->addMinutes($faker->randomElement([60, 90, 120, 180]));

            DB::table('lembur')->insert([
                'kode_lembur' => 'LB'.$tgl->format('Ymd').$faker->unique()->numberBetween(1000, 9999),
                'nik' => $k->nik,
                'tanggal_lembur' => $tgl->toDateString(),
                'pekerjaan' => $faker->randomElement(['Perbaikan unit excavator', 'Stock opname gudang', 'Penyelesaian laporan bulanan', 'Maintenance genset']),
                'tempat' => $faker->randomElement(['Workshop', 'Gudang utama', 'Kantor', 'Site']),
                'jam_mulai' => $mulai->format('H:i:s'),
                'jam_selesai' => $selesai->format('H:i:s'),
                'jam_selesai_awal' => $selesai->format('H:i:s'),
                'total_jam' => round($mulai->diffInMinutes($selesai) / 60, 2),
                'status_approved' => $faker->randomElement([0, 1, 1, 2]),
                'update_count' => 0,
                'created_at' => $mulai,
                'updated_at' => $selesai,
            ]);
        }
    }

    private function buatDinasLuar($faker, $karyawan): void
    {
        foreach ($karyawan->random(min(6, $karyawan->count())) as $k) {
            $mulai = Carbon::today()->addDays($faker->numberBetween(-20, 10));
            $status = $faker->randomElement(['menunggu', 'acc', 'acc', 'tolak']);

            DB::table('dinas_luar')->insert([
                'nik' => $k->nik,
                'tgl_mulai' => $mulai->toDateString(),
                'tgl_selesai' => $mulai->copy()->addDays($faker->numberBetween(0, 3))->toDateString(),
                'alasan' => $faker->randomElement(['Audit site', 'Meeting dengan klien', 'Pengambilan sparepart', 'Pelatihan K3']),
                'lokasi_tujuan' => $faker->city(),
                'transportasi' => $faker->randomElement(['darat', 'darat', 'udara', 'laut']),
                'dana_diajukan' => $faker->randomElement([500000, 1250000, 2500000]),
                'status_acc' => $status,
                'catatan_approval' => $status === 'tolak' ? 'Jadwal bentrok dengan kegiatan operasional.' : null,
                'approved_at' => $status === 'menunggu' ? null : now(),
                'created_at' => $mulai->copy()->subDays(3),
                'updated_at' => now(),
            ]);
        }
    }

    private function buatSuratPeringatan($faker, $karyawan): void
    {
        foreach ($karyawan->random(min(3, $karyawan->count()))->values() as $i => $k) {
            $terbit = Carbon::today()->subDays($faker->numberBetween(5, 150));

            DB::table('surat_peringatan')->insert([
                'nik' => $k->nik,
                'level' => $i + 1,
                'violation_type' => $faker->randomElement(['late', 'absent', 'discipline']),
                'issued_at' => $terbit->toDateString(),
                // Salah satu SP berakhir dalam 7 hari agar peringatan dashboard ikut tampil.
                'expires_at' => $i === 0 ? Carbon::today()->addDays(5)->toDateString() : $terbit->copy()->addMonths(6)->toDateString(),
                'note' => 'Data dummy.',
                'created_at' => $terbit,
                'updated_at' => $terbit,
            ]);
        }
    }

    private function buatKpiHarian($karyawan): void
    {
        $status = ['approved_by_hr', 'approved_by_hr', 'approved_by_atasan', 'submitted', 'draft', 'rejected'];

        foreach ($karyawan->take(12)->values() as $i => $k) {
            foreach (range(1, 5) as $hari) {
                $tgl = Carbon::today()->subWeekdays($hari);
                DB::table('kpi_daily')->insert([
                    'nik' => $k->nik,
                    'tanggal' => $tgl->toDateString(),
                    'status' => $status[($i + $hari) % count($status)],
                    'alasan_reject' => $status[($i + $hari) % count($status)] === 'rejected' ? 'Target belum sesuai.' : null,
                    'created_at' => $tgl->copy()->setTime(16, 30),
                    'updated_at' => $tgl->copy()->setTime(16, 30),
                ]);
            }
        }
    }

    /** Geser koordinat beberapa meter agar tiap presensi berbeda (GPS asli selalu bergeser). */
    private function geser($faker, string $koordinat): string
    {
        [$lat, $lon] = array_map('floatval', array_pad(explode(',', $koordinat), 2, 0));

        return sprintf('%.7f,%.7f', $lat + $faker->randomFloat(7, -0.0003, 0.0003), $lon + $faker->randomFloat(7, -0.0003, 0.0003));
    }

    private function bulatkan(string $koordinat): string
    {
        [$lat, $lon] = array_map('floatval', explode(',', $koordinat));

        return round($lat, 3).','.round($lon, 3);
    }
}
