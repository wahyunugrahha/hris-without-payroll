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
        Schema::create('lembur', function (Blueprint $table) {
            $table->id();
            $table->string('kode_lembur')->unique()->nullable();
            $table->string('nik');
            $table->date('tanggal_lembur');
            $table->string('pekerjaan');
            $table->string('tempat');
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->decimal('total_jam', 5, 2)->nullable();
            $table->text('foto_masuk')->nullable();
            $table->text('foto_keluar')->nullable();
            $table->text('keterangan')->nullable();
            $table->integer('status_approved')->default(0); // 0=pending, 1=approved, 2=rejected
            $table->timestamps();

            $table->foreign('nik')->references('nik')->on('karyawan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lembur');
    }
};
