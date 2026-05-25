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
        Schema::table('karyawan', function (Blueprint $table) {
            if (Schema::hasColumn('karyawan', 'nama')) {
                $table->dropColumn('nama_panggilan');
                $table->renameColumn('nama', 'nama_panggilan');
            }
            if (Schema::hasColumn('karyawan', 'status')) {
                $table->renameColumn('status', 'status_ptkp');
            }
            if (Schema::hasColumn('karyawan', 'tanggal_masuk')) {
                $table->renameColumn('tanggal_masuk', 'tanggal_awal_kontrak');
            }
            if (!Schema::hasColumn('karyawan', 'foto_bpjs_kesehatan')) {
                $table->string('foto_bpjs_kesehatan')->nullable();
            }
            if (!Schema::hasColumn('karyawan', 'foto_bpjs_ketenagakerjaan')) {
                $table->string('foto_bpjs_ketenagakerjaan')->nullable();
            }

            $table->char('kode_dept', 3)->nullable()->change();
            $table->string('kode_cabang', 8)->nullable()->change();
            $table->unsignedBigInteger('jabatan_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('karyawan', function (Blueprint $table) {
            if (!Schema::hasColumn('karyawan', 'nama')) {
                $table->renameColumn('nama_panggilan', 'nama');
                $table->addColumn('string', 'nama_panggilan')->nullable();
            }
            if (Schema::hasColumn('karyawan', 'status_ptkp')) {
                $table->renameColumn('status_ptkp', 'status');
            }
            if (Schema::hasColumn('karyawan', 'tanggal_awal_kontrak')) {
                $table->renameColumn('tanggal_awal_kontrak', 'tanggal_masuk');
            }
            
            if (Schema::hasColumn('karyawan', 'foto_bpjs_kesehatan')) {
                $table->dropColumn('foto_bpjs_kesehatan');
            }
            if (Schema::hasColumn('karyawan', 'foto_bpjs_ketenagakerjaan')) {
                $table->dropColumn('foto_bpjs_ketenagakerjaan');
            }
        });
    }
};
