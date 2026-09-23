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
        Schema::create('konfigurasi_jamkerja', function (Blueprint $table) {
            $table->string('nik', 50);
            $table->string('hari', 10);
            $table->char('kode_jam_kerja', 4)->nullable();

            $table->primary(['nik', 'hari']);

            // Foreign Key ke tabel karyawan
            $table->foreign('nik')->references('nik')->on('karyawan')->onDelete('cascade');

            // Foreign Key ke tabel jam_kerja (nullable untuk mendukung LIBUR)
            $table->foreign('kode_jam_kerja')->references('kode_jam_kerja')->on('jam_kerja')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nama tabel di down() harusnya 'konfigurasi_jamkerja', bukan 'setjamkerjas'
        Schema::dropIfExists('konfigurasi_jamkerja');
    }
};
