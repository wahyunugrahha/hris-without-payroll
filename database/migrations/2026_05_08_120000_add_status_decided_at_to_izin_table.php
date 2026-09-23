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
        Schema::table('izin', function (Blueprint $table) {
            if (! Schema::hasColumn('izin', 'status_decided_at')) {
                $table->timestamp('status_decided_at')->nullable()->after('status_approved');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('izin', function (Blueprint $table) {
            if (Schema::hasColumn('izin', 'status_decided_at')) {
                $table->dropColumn('status_decided_at');
            }
        });
    }
};
