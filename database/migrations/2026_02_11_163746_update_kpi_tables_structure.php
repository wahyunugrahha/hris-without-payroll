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
        // 1. Hapus kolom approve lama (karena tipenya masih timestamp)
        Schema::table('kpi_daily', function (Blueprint $table) {
            $table->dropColumn(['approve_atasan', 'approve_hr']);
        });

        // 2. Buat ulang kolom dengan tipe string dan tambahkan kolom baru
        Schema::table('kpi_daily', function (Blueprint $table) {
            // Kolom penyimpan NIK / ID
            $table->string('approve_atasan')->nullable()->comment('Menyimpan NIK Atasan yang ACC');
            $table->string('approve_hr')->nullable()->comment('Menyimpan NIK HR yang ACC');

            // Kolom penyimpan Waktu (Timestamp)
            $table->timestamp('approve_atasan_at')->nullable()->comment('Waktu atasan ACC');
            $table->timestamp('approve_hr_at')->nullable()->comment('Waktu HR ACC');

            // Kolom alasan reject
            $table->text('alasan_reject')->nullable()->comment('Catatan revisi dari atasan/hr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback: Hapus semua kolom yang baru dibuat
        Schema::table('kpi_daily', function (Blueprint $table) {
            $table->dropColumn([
                'approve_atasan',
                'approve_hr',
                'approve_atasan_at',
                'approve_hr_at',
                'alasan_reject',
            ]);
        });

        // Rollback: Kembalikan kolom ke tipe awal (timestamp)
        Schema::table('kpi_daily', function (Blueprint $table) {
            $table->timestamp('approve_atasan')->nullable();
            $table->timestamp('approve_hr')->nullable();
        });
    }
};
