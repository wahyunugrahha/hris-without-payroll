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
        Schema::table('kpi_master', function (Blueprint $table) {
            $table->dropUnique('kpi_master_kode_master_unique');

            $table->decimal('bobot_kpi', 8, 2)->nullable()->after('kode_cabang');
            $table->decimal('target_kpi', 8, 2)->nullable()->after('bobot_kpi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kpi_master', function (Blueprint $table) {
            $table->dropColumn(['bobot_kpi', 'target_kpi']);
            $table->unique('kode_master', 'kpi_master_kode_master_unique');
        });
    }
};
