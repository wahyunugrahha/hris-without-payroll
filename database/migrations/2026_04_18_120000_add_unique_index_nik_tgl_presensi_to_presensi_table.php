<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $hasDuplicates = DB::table('presensi')
            ->select('nik', 'tgl_presensi', DB::raw('COUNT(*) as total'))
            ->groupBy('nik', 'tgl_presensi')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicates) {
            throw new RuntimeException('Masih ada data presensi duplikat. Jalankan: php artisan presensi:clean-duplicate');
        }

        Schema::table('presensi', function (Blueprint $table) {
            $table->unique(['nik', 'tgl_presensi'], 'presensi_nik_tgl_presensi_unique');
        });
    }

    public function down(): void
    {
        Schema::table('presensi', function (Blueprint $table) {
            $table->dropUnique('presensi_nik_tgl_presensi_unique');
        });
    }
};
