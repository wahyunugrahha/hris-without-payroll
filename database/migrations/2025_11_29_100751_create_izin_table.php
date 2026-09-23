<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('izin', function (Blueprint $table) {
            $table->char('kode_izin', 10)->primary();

            $table->string('nik', 50);
            $table->foreign('nik')->references('nik')->on('karyawan')->onDelete('cascade');
            $table->date('tgl_izin_dari');
            $table->date('tgl_izin_sampai');
            $table->char('status', 1)->comment('i: izin; s: sakit');
            $table->string('keterangan', 255)->nullable();
            $table->string('doc_sid', 255)->nullable();
            $table->char('status_approved', 1)->default('0')->comment('0: Pending; 1: Disetujui; 2: Ditolak');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('izin');
    }
};
