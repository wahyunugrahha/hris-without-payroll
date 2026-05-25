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
        Schema::create('hari_libur', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_libur');
            $table->string('keterangan');
            $table->enum('jenis_libur', ['nasional', 'cuti_bersama', 'lainnya'])->default('nasional');
            $table->string('kode_cabang')->nullable();
            $table->timestamps();
            
            // Index untuk pencarian cepat berdasarkan tanggal
            $table->index('tanggal_libur');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hari_libur');
    }
};
