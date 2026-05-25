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
        Schema::table('rekap_bulanans', function (Blueprint $table) {
            $table->time('avg_jam_masuk')->nullable()->after('total_izin_sakit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rekap_bulanans', function (Blueprint $table) {
            $table->dropColumn('avg_jam_masuk');
        });
    }
};
