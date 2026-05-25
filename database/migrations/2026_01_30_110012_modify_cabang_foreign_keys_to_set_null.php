<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Modify karyawan table
        Schema::table('karyawan', function (Blueprint $table) {
            $table->dropForeign(['kode_cabang']);
        });
        
        Schema::table('karyawan', function (Blueprint $table) {
            $table->string('kode_cabang', 8)->nullable()->change();
            $table->foreign('kode_cabang')
                ->references('kode_cabang')
                ->on('cabang')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });

        // 2. Modify konfigurasi_jk_dept table
        Schema::table('konfigurasi_jk_dept', function (Blueprint $table) {
            $table->dropForeign(['kode_cabang']);
        });
        
        Schema::table('konfigurasi_jk_dept', function (Blueprint $table) {
            $table->foreign('kode_cabang')
                ->references('kode_cabang')
                ->on('cabang')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });

        // 3. Modify cabang_lokasis table - add foreign key
        // Clean up orphaned records first
        DB::statement('DELETE FROM cabang_lokasis WHERE kode_cabang NOT IN (SELECT kode_cabang FROM cabang)');
        
        Schema::table('cabang_lokasis', function (Blueprint $table) {
            $table->foreign('kode_cabang')
                ->references('kode_cabang')
                ->on('cabang')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });

        // 4. Modify kpi_master table
        Schema::table('kpi_master', function (Blueprint $table) {
            $table->dropForeign(['kode_cabang']);
        });
        
        Schema::table('kpi_master', function (Blueprint $table) {
            $table->foreign('kode_cabang')
                ->references('kode_cabang')
                ->on('cabang')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback karyawan
        Schema::table('karyawan', function (Blueprint $table) {
            $table->dropForeign(['kode_cabang']);
        });
        
        Schema::table('karyawan', function (Blueprint $table) {
            $table->string('kode_cabang', 8)->nullable(false)->change();
            $table->foreign('kode_cabang')
                ->references('kode_cabang')
                ->on('cabang')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        // Rollback konfigurasi_jk_dept
        Schema::table('konfigurasi_jk_dept', function (Blueprint $table) {
            $table->dropForeign(['kode_cabang']);
        });
        
        Schema::table('konfigurasi_jk_dept', function (Blueprint $table) {
            $table->foreign('kode_cabang')
                ->references('kode_cabang')
                ->on('cabang')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        // Rollback cabang_lokasis
        Schema::table('cabang_lokasis', function (Blueprint $table) {
            $table->dropForeign(['kode_cabang']);
        });

        // Rollback kpi_master
        Schema::table('kpi_master', function (Blueprint $table) {
            $table->dropForeign(['kode_cabang']);
        });
        
        Schema::table('kpi_master', function (Blueprint $table) {
            $table->foreign('kode_cabang')
                ->references('kode_cabang')
                ->on('cabang');
        });
    }
};
