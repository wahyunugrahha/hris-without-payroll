<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. SURAT PERINGATAN (Target: Cascade)
        if (Schema::hasTable('surat_peringatan')) {

            // STEP A: Bersihkan data orphan dulu!
            // Hapus surat_peringatan yang NIK-nya tidak ada di tabel karyawan
            DB::table('surat_peringatan')
                ->whereNotIn('nik', function ($query) {
                    $query->select('nik')->from('karyawan');
                })
                ->delete();

            // STEP B: Tambahkan Foreign Key
            Schema::table('surat_peringatan', function (Blueprint $table) {
                // Pastikan indeks ada untuk performa (opsional jika sudah ada, tapi aman ditaruh)
                // $table->index('nik'); 

                $table->foreign('nik')
                    ->references('nik')
                    ->on('karyawan')
                    ->onDelete('cascade') // Hapus SP jika Karyawan dihapus
                    ->onUpdate('cascade');
            });
        }

        // 2. LEADERBOARD SNAPSHOTS (Target: Set Null)
        if (Schema::hasTable('leaderboard_snapshots')) {

            Schema::table('leaderboard_snapshots', function (Blueprint $table) {
                // STEP A: Ubah kolom jadi NULLABLE dulu
                // Syarat wajib untuk on delete SET NULL
                $table->string('nik')->nullable()->change();

                // STEP B: Tambahkan Foreign Key
                $table->foreign('nik')
                    ->references('nik')
                    ->on('karyawan')
                    ->onDelete('set null') // Set NULL jika Karyawan dihapus
                    ->onUpdate('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback: Hapus FK

        if (Schema::hasTable('surat_peringatan')) {
            Schema::table('surat_peringatan', function (Blueprint $table) {
                $table->dropForeign(['nik']);
            });
        }

        if (Schema::hasTable('leaderboard_snapshots')) {
            Schema::table('leaderboard_snapshots', function (Blueprint $table) {
                $table->dropForeign(['nik']);

                // Opsional: Kembalikan jadi not null (biasanya tidak perlu/berisiko error data)
                // $table->string('nik')->nullable(false)->change(); 
            });
        }
    }
};