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
        Schema::table('leaderboard_snapshots', function (Blueprint $table) {
            $table->time('jam_out')->nullable()->after('jam_in');
            $table->time('jadwal_masuk')->nullable()->after('jam_out');
            $table->time('jadwal_pulang')->nullable()->after('jadwal_masuk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leaderboard_snapshots', function (Blueprint $table) {
            $table->dropColumn(['jam_out', 'jadwal_masuk', 'jadwal_pulang']);
        });
    }
};
