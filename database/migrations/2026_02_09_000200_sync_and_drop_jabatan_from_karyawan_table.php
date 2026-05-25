<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('karyawan', 'jabatan_id') && Schema::hasColumn('karyawan', 'jabatan')) {
            // Sync jabatan_id from jabatan text (case-insensitive match)
            DB::statement("
                UPDATE karyawan k
                SET jabatan_id = j.id
                FROM jabatan j
                WHERE k.jabatan_id IS NULL
                  AND k.jabatan IS NOT NULL
                  AND lower(trim(k.jabatan)) = lower(trim(j.nama_jabatan))
            ");

            // Sync jabatan text from jabatan_id
            DB::statement("
                UPDATE karyawan k
                SET jabatan = j.nama_jabatan
                FROM jabatan j
                WHERE k.jabatan_id = j.id
                  AND (k.jabatan IS NULL OR trim(k.jabatan) = '' OR k.jabatan <> j.nama_jabatan)
            ");
        }

        Schema::table('karyawan', function (Blueprint $table) {
            if (Schema::hasColumn('karyawan', 'jabatan')) {
                $table->dropColumn('jabatan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('karyawan', function (Blueprint $table) {
            if (!Schema::hasColumn('karyawan', 'jabatan')) {
                $table->string('jabatan', 255)->nullable();
            }
        });
    }
};
