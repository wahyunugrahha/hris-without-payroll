<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     * Mengatur foreign key master data agar menggunakan ON DELETE SET NULL untuk data utama (Karyawan/User)
     * dan ON DELETE CASCADE untuk data konfigurasi murni (Multilokasi/Detail Jam Kerja).
     */
    public function up(): void
    {
        // --- 0. SINKRONISASI KOLOM NULLABLE ---
        // Kolom users.kode_dept harus diubah menjadi nullable agar mendukung SET NULL saat departemen dihapus.
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'kode_dept')) {
            Schema::table('users', function (Blueprint $table) {
                $table->char('kode_dept', 3)->nullable()->change();
            });
        }

        // --- 1. DEPARTEMEN (kode_dept) ---
        // Data karyawan & admin diset ke NULL saat departemen dihapus (Data aman)
        $this->updateFK('karyawan', 'kode_dept', 'departemen', 'kode_dept', 'set null');
        $this->updateFK('users', 'kode_dept', 'departemen', 'kode_dept', 'set null');
        
        // Data konfigurasi murni didelete berantai (CASCADE)
        $this->updateFK('konfigurasi_jk_dept', 'kode_dept', 'departemen', 'kode_dept', 'cascade');
        $this->updateFK('hari_libur', 'kode_dept', 'departemen', 'kode_dept', 'cascade');
        $this->updateFK('kpi_master', 'kode_dept', 'departemen', 'kode_dept', 'cascade');

        // --- 2. CABANG (kode_cabang) ---
        // Data karyawan & admin diset ke NULL saat cabang dihapus (Data aman)
        $this->updateFK('karyawan', 'kode_cabang', 'cabang', 'kode_cabang', 'set null');
        $this->updateFK('users', 'kode_cabang', 'cabang', 'kode_cabang', 'set null');
        
        // Data lokasi & konfigurasi cabang didelete berantai (CASCADE)
        $this->updateFK('kpi_master', 'kode_cabang', 'cabang', 'kode_cabang', 'cascade');
        $this->updateFK('cabang_lokasi', 'kode_cabang', 'cabang', 'kode_cabang', 'cascade');

        // --- 3. JAM KERJA (kode_jam_kerja) ---
        // Riwayat presensi diset ke NULL jika jam kerja dihapus (Mencegah kehilangan presensi)
        $this->updateFK('presensi', 'kode_jam_kerja', 'jam_kerja', 'kode_jam_kerja', 'set null');
        
        // Konfigurasi shift detail & personal didelete berantai (CASCADE)
        $this->updateFK('konfigurasi_jk_dept_detail', 'kode_jam_kerja', 'jam_kerja', 'kode_jam_kerja', 'cascade');
        $this->updateFK('setjamkerja', 'kode_jam_kerja', 'jam_kerja', 'kode_jam_kerja', 'cascade');

        // --- 4. MASTER CUTI (kode_cuti) ---
        // Riwayat izin/cuti diset ke NULL jika kode cuti dihapus
        $this->updateFK('izin', 'kode_cuti', 'master_cuti', 'kode_cuti', 'set null');
    }

    /**
     * Helper untuk drop dan update Foreign Key dengan ON UPDATE CASCADE
     */
    private function updateFK($table, $column, $refTable, $refColumn, $onDelete)
    {
        if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
            $constraints = [
                "{$table}_{$column}_foreign",
                "fk_{$table}_{$column}",
            ];

            foreach ($constraints as $constraint) {
                try {
                    DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$constraint}");
                } catch (\Exception $e) {
                    // Ignore jika constraint tidak ada
                }
            }

            // Tambahkan FK baru dengan ON DELETE yang diatur dan ON UPDATE CASCADE
            $newConstraint = "{$table}_{$column}_foreign";
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$newConstraint} 
                            FOREIGN KEY ({$column}) REFERENCES {$refTable}({$refColumn}) 
                            ON DELETE {$onDelete} ON UPDATE CASCADE");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert users.kode_dept kembali ke NOT NULL
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'kode_dept')) {
            Schema::table('users', function (Blueprint $table) {
                $table->char('kode_dept', 3)->nullable(false)->change();
            });
        }

        // Kembalikan FK asal ke mode RESTRICT/DEFAULT
        $this->updateFK('karyawan', 'kode_dept', 'departemen', 'kode_dept', 'restrict');
        $this->updateFK('users', 'kode_dept', 'departemen', 'kode_dept', 'restrict');
        $this->updateFK('karyawan', 'kode_cabang', 'cabang', 'kode_cabang', 'restrict');
        $this->updateFK('users', 'kode_cabang', 'cabang', 'kode_cabang', 'restrict');
        $this->updateFK('presensi', 'kode_jam_kerja', 'jam_kerja', 'kode_jam_kerja', 'restrict');
        $this->updateFK('izin', 'kode_cuti', 'master_cuti', 'kode_cuti', 'restrict');
    }
};
