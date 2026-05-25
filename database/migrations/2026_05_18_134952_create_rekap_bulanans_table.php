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
        Schema::create('rekap_bulanans', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 50)->index();
            $table->string('kode_cabang', 20)->index();
            $table->integer('bulan');
            $table->integer('tahun');
            $table->integer('total_poin')->default(0);
            $table->integer('total_poin_kpi')->default(0);
            $table->integer('total_izin_sakit')->default(0);
            $table->decimal('bonus_bulanan', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['nik', 'bulan', 'tahun'], 'rekap_bulanan_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekap_bulanans');
    }
};
