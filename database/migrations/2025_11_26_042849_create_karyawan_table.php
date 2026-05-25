<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('karyawan', function (Blueprint $table) {
            $table->string('nik', 50)->primary();
            $table->string('nama', 255);
            $table->string('nama_lengkap', 255);
            $table->string('jabatan', 255);
            $table->string('no_hp', 20);
            $table->string('foto', 255)->nullable();
            $table->char('kode_dept', 3);
            $table->string('kode_cabang', 8);
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();

            // Foreign Key untuk Departemen
            $table->foreign('kode_dept')
                ->references('kode_dept')
                ->on('departemen')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Foreign Key untuk Cabang
            $table->foreign('kode_cabang')
                ->references('kode_cabang')
                ->on('cabang')
                ->cascadeOnUpdate()
                ->restrictOnDelete(); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('karyawan');
    }
};