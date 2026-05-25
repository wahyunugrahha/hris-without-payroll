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
        Schema::create('kpi_master', function (Blueprint $table) {
            $table->id();
            $table->string('kode_master')->unique();
            $table->string('nama_kpi');
            $table->string('kode_dept');
            $table->string('kode_cabang');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('kode_dept')->references('kode_dept')->on('departemen');
            $table->foreign('kode_cabang')->references('kode_cabang')->on('cabang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_master');
    }
};
