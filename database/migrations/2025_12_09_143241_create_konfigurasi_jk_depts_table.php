<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('konfigurasi_jk_dept', function (Blueprint $table) {

            $table->char('kode_jk_dept', 50)->primary();
            $table->string('kode_cabang', 8);
            $table->char('kode_dept', 3);
            $table->timestamps();

            // Definisi Foreign Key (Relasi)
            // Relasi ke tabel 'cabang'
            $table->foreign('kode_cabang')
                ->references('kode_cabang')
                ->on('cabang')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            // Relasi ke tabel 'departemen'
            $table->foreign('kode_dept')
                ->references('kode_dept')
                ->on('departemen')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->unique(['kode_cabang', 'kode_dept']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('konfigurasi_jk_dept');
    }
};
