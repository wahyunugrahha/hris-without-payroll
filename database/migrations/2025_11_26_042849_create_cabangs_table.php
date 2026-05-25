<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cabang', function (Blueprint $table) {
            $table->string('kode_cabang', length: 8)->primary();
            $table->string('nama_cabang', 50)->nullable(false);
            $table->string('lokasi_kantor', 255)->nullable(false);
            $table->smallInteger('radius')->nullable(false);
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('cabang');
    }
};