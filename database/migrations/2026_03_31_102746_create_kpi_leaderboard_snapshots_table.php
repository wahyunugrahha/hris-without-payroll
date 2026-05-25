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
        Schema::create('kpi_leaderboard_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('kode_cabang');
            $table->integer('rank');
            $table->integer('points');
            $table->string('nik');
            $table->string('nama_lengkap');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_leaderboard_snapshots');
    }
};
