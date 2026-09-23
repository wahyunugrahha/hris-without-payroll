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
        Schema::table('kpi_master_detail', function (Blueprint $table) {
            $table->dropColumn('input_by');
            $table->renameColumn('bobot', 'score_indikator');
        });

        if (Schema::hasTable('kpi_monthly')) {
            Schema::rename('kpi_monthly', 'kpi_report');

            Schema::table('kpi_report', function (Blueprint $table) {
                // Tambah relasi kode_master
                $table->string('kode_master')->nullable()->after('nik');

                // Tambah rincian skor
                $table->decimal('score_presensi', 8, 2)->nullable()->after('periode_tahun');
                $table->decimal('score_workbook', 8, 2)->nullable()->after('score_presensi');
                $table->decimal('score_atasan', 8, 2)->nullable()->after('score_workbook');
                $table->renameColumn('avg_score', 'final_score');

                // Hapus kolom status (karena di gambar ERD kpi_report tidak ada status)
                if (Schema::hasColumn('kpi_report', 'status')) {
                    $table->dropColumn('status');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        if (Schema::hasTable('kpi_report')) {
            Schema::table('kpi_report', function (Blueprint $table) {
                $table->renameColumn('final_score', 'avg_score');
                $table->dropColumn(['kode_master', 'score_presensi', 'score_workbook', 'score_atasan']);
                $table->string('status')->nullable();
            });
            Schema::rename('kpi_report', 'kpi_monthly');
        }

        Schema::table('kpi_master_detail', function (Blueprint $table) {
            $table->string('input_by', 20)->default('self')->after('indikator');
            $table->integer('target')->nullable()->after('input_by');
            $table->renameColumn('score_indikator', 'bobot');
        });
    }
};
