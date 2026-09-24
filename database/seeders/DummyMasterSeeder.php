<?php

namespace Database\Seeders;

use App\Models\Jabatan;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Data dummy non-karyawan: hari libur, pengumuman, titik lokasi cabang, dan master KPI
 * (indikator workbook + penilaian atasan) untuk departemen karyawan dummy.
 *
 * Dipanggil oleh DummyDataSeeder (setelah data dummy karyawan lama dihapus). Semua data
 * dikenali lewat penanda tetap (kode KPI-DMY-*, judul/keterangan di bawah) sehingga aman
 * dijalankan ulang tanpa menyentuh data asli.
 */
class DummyMasterSeeder extends Seeder
{
    public const PREFIX_KPI = 'KPI-DMY-';

    /** Keterangan hari libur => [selisih hari dari hari ini, jenis, cabang, departemen]. */
    private const HARI_LIBUR = [
        'Libur Nasional (dummy)' => [12, 'nasional', null, null],
        'Cuti Bersama (dummy)' => [13, 'cuti_bersama', null, null],
        'Hari Jadi Kota (dummy)' => [-9, 'lokal', 'CBNG0001', null],
        'Libur Departemen Mekanik (dummy)' => [20, 'lokal', 'CBNG0001', 'MKN'],
        'Libur Nasional Lalu (dummy)' => [-25, 'nasional', null, null],
    ];

    /** Judul pengumuman => [mulai (hari), selesai (hari), aktif]. */
    private const PENGUMUMAN = [
        'Jadwal Medical Check-up Karyawan' => [-3, 10, true],
        'Pembaruan Kebijakan Lembur' => [0, 30, true],
        'Libur Akhir Tahun' => [15, 25, true],
        'Rapat Umum Bulan Lalu' => [-40, -30, false],
    ];

    /** Nama titik lokasi tambahan per cabang => [geser lat, geser lon, radius, aktif]. */
    private const TITIK_LOKASI = [
        'CBNG0001' => ['Gerbang Utama (dummy)' => [0.0008, 0.0006, 80, true], 'Gudang Sparepart (dummy)' => [-0.0011, 0.0009, 60, true], 'Workshop Lama (dummy)' => [0.0015, -0.0012, 50, false]],
        'HOJKT01' => ['Lobby Gedung B (dummy)' => [0.0004, -0.0005, 40, true]],
    ];

    /** Indikator workbook harian & penilaian atasan untuk master KPI dummy. */
    private const INDIKATOR_KARYAWAN = [
        'Menyelesaikan tugas harian sesuai target',
        'Mengisi laporan kerja sebelum pulang',
        'Menjaga kebersihan & kerapian area kerja',
        'Mematuhi SOP dan K3',
    ];

    private const INDIKATOR_ATASAN = [
        ['Kerja sama tim', 15, 90],
        ['Inisiatif & tanggung jawab', 15, 85],
    ];

    public function run(array $penempatan = []): void
    {
        $this->hapusDataLama();
        $this->buatHariLibur();
        $this->buatPengumuman();
        $this->buatTitikLokasi();
        $this->buatMasterKpi($penempatan);
    }

    public static function hapusDataLama(): void
    {
        DB::table('kpi_master_atasan')->where('kode_master', 'like', self::PREFIX_KPI.'%')->delete();
        DB::table('kpi_master_detail')->where('kode_master', 'like', self::PREFIX_KPI.'%')->delete();
        DB::table('kpi_master')->where('kode_master', 'like', self::PREFIX_KPI.'%')->delete();
        DB::table('hari_libur')->whereIn('keterangan', array_keys(self::HARI_LIBUR))->delete();
        DB::table('pengumuman')->whereIn('judul', array_keys(self::PENGUMUMAN))->delete();
        DB::table('cabang_lokasis')->whereIn('nama_lokasi', collect(self::TITIK_LOKASI)->flatMap(fn ($t) => array_keys($t)))->delete();
    }

    private function buatHariLibur(): void
    {
        foreach (self::HARI_LIBUR as $keterangan => [$hari, $jenis, $cabang, $dept]) {
            DB::table('hari_libur')->insert([
                'tanggal_libur' => Carbon::today()->addDays($hari)->toDateString(),
                'keterangan' => $keterangan,
                'jenis_libur' => $jenis,
                'kode_cabang' => $cabang,
                'kode_dept' => $dept,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function buatPengumuman(): void
    {
        foreach (self::PENGUMUMAN as $judul => [$mulai, $selesai, $aktif]) {
            DB::table('pengumuman')->insert([
                'judul' => $judul,
                'isi' => 'Informasi untuk seluruh karyawan terkait '.mb_strtolower($judul).'. Detail lengkap dapat ditanyakan ke HRD.',
                'gambar' => null,
                'tanggal_mulai' => Carbon::today()->addDays($mulai)->toDateString(),
                'tanggal_selesai' => Carbon::today()->addDays($selesai)->toDateString(),
                'is_active' => $aktif,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function buatTitikLokasi(): void
    {
        $lokasiKantor = DB::table('cabang')->pluck('lokasi_kantor', 'kode_cabang');

        foreach (self::TITIK_LOKASI as $kodeCabang => $titik) {
            if (! isset($lokasiKantor[$kodeCabang])) {
                continue;
            }
            [$lat, $lon] = array_map('floatval', array_pad(explode(',', (string) $lokasiKantor[$kodeCabang]), 2, 0));

            foreach ($titik as $nama => [$dLat, $dLon, $radius, $aktif]) {
                DB::table('cabang_lokasis')->insert([
                    'kode_cabang' => $kodeCabang,
                    'nama_lokasi' => $nama,
                    'latitude' => round($lat + $dLat, 7),
                    'longitude' => round($lon + $dLon, 7),
                    'radius' => $radius,
                    'aktif' => $aktif,
                ]);
            }
        }
    }

    /**
     * Satu master per departemen (jabatan "Staff", satu baris per cabang) agar setiap karyawan
     * dummy punya KPI lewat fallback KPIMaster::untukKaryawan().
     *
     * @param  array<string, string[]>  $penempatan  kode cabang => kode departemen
     */
    private function buatMasterKpi(array $penempatan): void
    {
        $cabangPerDept = [];
        foreach ($penempatan as $kodeCabang => $daftarDept) {
            foreach ($daftarDept as $kodeDept) {
                $cabangPerDept[$kodeDept][] = $kodeCabang;
            }
        }

        $staff = Jabatan::where('nama_jabatan', 'Staff')->value('id');
        if (! $staff) {
            return;
        }
        $namaDept = DB::table('departemen')->whereIn('kode_dept', array_keys($cabangPerDept))->pluck('nama_dept', 'kode_dept');

        foreach ($namaDept as $kodeDept => $nama) {
            $kode = self::PREFIX_KPI.$kodeDept;
            foreach ($cabangPerDept[$kodeDept] as $kodeCabang) {
                DB::table('kpi_master')->insert([
                    'kode_master' => $kode,
                    'nama_kpi' => 'KPI Harian '.$nama,
                    'kode_dept' => $kodeDept,
                    'kode_cabang' => $kodeCabang,
                    'jabatan_id' => $staff,
                    'is_active' => true,
                    'bobot_kpi' => 40,
                    'target_kpi' => 90,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach (self::INDIKATOR_KARYAWAN as $indikator) {
                DB::table('kpi_master_detail')->insert([
                    'kode_master' => $kode,
                    'indikator' => $indikator,
                    'score_indikator' => 10,
                    'target' => 100,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach (self::INDIKATOR_ATASAN as [$indikator, $bobot, $target]) {
                DB::table('kpi_master_atasan')->insert([
                    'kode_master' => $kode,
                    'indikator' => $indikator,
                    'bobot_atasan' => $bobot,
                    'target_atasan' => $target,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
