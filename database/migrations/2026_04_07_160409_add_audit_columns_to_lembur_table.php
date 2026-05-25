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
        Schema::table('lembur', function (Blueprint $table) {
            $table->time('jam_selesai_awal')->nullable()->after('jam_selesai');
            $table->integer('update_count')->default(0)->after('status_approved');
            $table->timestamp('last_update_at')->nullable()->after('update_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lembur', function (Blueprint $table) {
            $table->dropColumn(['jam_selesai_awal', 'update_count', 'last_update_at']);
        });
    }
};
