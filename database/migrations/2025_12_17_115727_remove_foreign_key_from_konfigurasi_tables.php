<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop foreign key dari konfigurasi_jamkerja
        Schema::table('konfigurasi_jamkerja', function (Blueprint $table) {
            $table->dropForeign(['kode_jam_kerja']);
        });

        // Drop foreign key dari konfigurasi_jk_dept_detail
        Schema::table('konfigurasi_jk_dept_detail', function (Blueprint $table) {
            $table->dropForeign(['kode_jam_kerja']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore foreign key ke konfigurasi_jamkerja
        Schema::table('konfigurasi_jamkerja', function (Blueprint $table) {
            $table->foreign('kode_jam_kerja')
                ->references('kode_jam_kerja')
                ->on('jam_kerja')
                ->onDelete('cascade');
        });

        // Restore foreign key ke konfigurasi_jk_dept_detail
        Schema::table('konfigurasi_jk_dept_detail', function (Blueprint $table) {
            $table->foreign('kode_jam_kerja')
                ->references('kode_jam_kerja')
                ->on('jam_kerja')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }
};
