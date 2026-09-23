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
        Schema::table('presensi', function (Blueprint $table) {
            // Menambahkan kolom 'status' dengan tipe CHAR(1)
            // Default 'h' (Hadir) untuk baris presensi normal
            // Ditempatkan setelah 'lokasi_out'
            $table->char('status', 1)->default('h')->after('lokasi_out');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presensi', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
