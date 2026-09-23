<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presensi', function (Blueprint $table) {
            // Catatan indikasi fake GPS / data lokasi janggal untuk ditinjau HR (presensi tetap tersimpan).
            $table->text('kejanggalan')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('presensi', function (Blueprint $table) {
            $table->dropColumn('kejanggalan');
        });
    }
};
