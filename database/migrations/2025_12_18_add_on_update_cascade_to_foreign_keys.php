<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'presensi',
            'izin',
            'setjamkerja',
            'konfigurasi_jamkerja',
            'konfigurasi_jk_dept',
        ];

        foreach ($tables as $table) {
            // Cek apakah tabel ada DAN kolom 'nik' ada di tabel tersebut
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'nik')) {
                $constraint = "{$table}_nik_foreign";

                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$constraint}");
                DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$constraint} 
                                FOREIGN KEY (nik) REFERENCES karyawan(nik) 
                                ON DELETE CASCADE ON UPDATE CASCADE");
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'presensi',
            'izin',
            'setjamkerja',
            'konfigurasi_jamkerja',
            'konfigurasi_jk_dept',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'nik')) {
                $constraint = "{$table}_nik_foreign";

                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$constraint}");
                DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$constraint} 
                                FOREIGN KEY (nik) REFERENCES karyawan(nik) 
                                ON DELETE CASCADE");
            }
        }
    }
};
