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
        Schema::create('master_cuti', function (Blueprint $table) {
            $table->char('kode_cuti', 3)->primary();
            $table->string('nama_cuti', 30);
            $table->smallInteger('jml_hari');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_cuti');
    }
};
