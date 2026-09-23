<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Bersihkan duplikat sebelum menambah unique index.
        // Simpan baris dengan id terbesar (snapshot terbaru) untuk setiap kombinasi unik.
        DB::statement('
            DELETE FROM leaderboard_snapshots a
            USING leaderboard_snapshots b
            WHERE a.id < b.id
              AND a.date = b.date
              AND a.kode_cabang = b.kode_cabang
              AND a.nik = b.nik
        ');

        DB::statement('
            DELETE FROM kpi_leaderboard_snapshots a
            USING kpi_leaderboard_snapshots b
            WHERE a.id < b.id
              AND a.date = b.date
              AND a.kode_cabang = b.kode_cabang
              AND a.nik = b.nik
        ');

        Schema::table('leaderboard_snapshots', function (Blueprint $table) {
            $table->unique(['date', 'kode_cabang', 'nik'], 'leaderboard_snapshots_date_cabang_nik_unique');
        });

        Schema::table('kpi_leaderboard_snapshots', function (Blueprint $table) {
            $table->unique(['date', 'kode_cabang', 'nik'], 'kpi_leaderboard_snapshots_date_cabang_nik_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leaderboard_snapshots', function (Blueprint $table) {
            $table->dropUnique('leaderboard_snapshots_date_cabang_nik_unique');
        });

        Schema::table('kpi_leaderboard_snapshots', function (Blueprint $table) {
            $table->dropUnique('kpi_leaderboard_snapshots_date_cabang_nik_unique');
        });
    }
};
