<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('kpi_atasan_daily', function (Blueprint $table) {
            $table->id();
            $table->string('nik'); // NIK Karyawan yang dinilai
            $table->date('tanggal'); // Penanda periode harian
            $table->string('input_atasan'); // NIK Atasan yang menilai
            $table->string('approve_hr')->nullable(); // NIK / User ID HRD
            $table->timestamp('approve_hr_at')->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved_by_hr'])->default('draft');
            $table->timestamps();

            // Mencegah atasan menilai karyawan yang sama 2x di tanggal yang sama
            $table->unique(['nik', 'tanggal']);

            // Foreign key ke tabel karyawan
            $table->foreign('nik')->references('nik')->on('karyawan')->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('kpi_atasan_daily');
    }
};
