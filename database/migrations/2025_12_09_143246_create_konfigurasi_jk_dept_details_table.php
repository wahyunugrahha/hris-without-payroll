<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('konfigurasi_jk_dept_detail', function (Blueprint $table) {

            // Kolom Foreign Key (FK) ke konfigurasi_jk_dept
            $table->char('kode_jk_dept', 50);

            // Kolom untuk Hari 
            $table->char('hari', length: 10);

            // Kolom Foreign Key (FK) untuk Jam Kerja (kode_jam_kerja) - nullable untuk LIBUR
            $table->char('kode_jam_kerja', 4)->nullable();

            // Kolom Lainnya
            $table->timestamps();

            // Definisi Primary Key (PK)
            $table->primary(['kode_jk_dept', 'hari']);

            // Definisi Foreign Key (Relasi)

            // Relasi ke tabel 'konfigurasi_jk_dept'
            $table->foreign('kode_jk_dept')
                ->references('kode_jk_dept')
                ->on('konfigurasi_jk_dept')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // Relasi ke tabel 'jam_kerja' (nullable untuk mendukung LIBUR)
            $table->foreign('kode_jam_kerja')
                ->references('kode_jam_kerja')
                ->on('jam_kerja')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('konfigurasi_jk_dept_detail');
    }
};