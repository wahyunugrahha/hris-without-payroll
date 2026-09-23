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
        Schema::table('leaderboard_snapshots', function (Blueprint $table) {
            // Tambah kolom points, default 0
            $table->integer('points')->default(0)->after('rank');
        });
    }

    public function down()
    {
        Schema::table('leaderboard_snapshots', function (Blueprint $table) {
            $table->dropColumn('points');
        });
    }
};
