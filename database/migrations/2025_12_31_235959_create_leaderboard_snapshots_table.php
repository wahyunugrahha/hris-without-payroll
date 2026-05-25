<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('leaderboard_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->string('kode_cabang')->nullable()->index();
            $table->integer('rank')->nullable();
            $table->string('nik')->nullable()->index();
            $table->string('nama_lengkap')->nullable();
            $table->time('jam_in')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('leaderboard_snapshots');
    }
};
