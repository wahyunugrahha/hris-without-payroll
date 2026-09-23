<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_master_detail', function (Blueprint $col) {
            $col->string('kode_master')->after('kpi_master_id')->nullable()->index();
        });

        // Gunakan statement native agar tidak bentrok dengan Query Builder pgsql
        DB::statement('
            UPDATE kpi_master_detail 
            SET kode_master = kpi_master.kode_master 
            FROM kpi_master 
            WHERE kpi_master_detail.kpi_master_id = kpi_master.id
        ');

        Schema::table('kpi_master_detail', function (Blueprint $col) {
            // Cek nama constraint di pgsql biasanya: kpi_master_detail_kpi_master_id_foreign
            $col->dropColumn('kpi_master_id');
            $col->string('kode_master')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('kpi_master_detail', function (Blueprint $col) {
            $col->unsignedBigInteger('kpi_master_id')->after('id')->nullable();
            $col->dropColumn('kode_master');
        });
    }
};
