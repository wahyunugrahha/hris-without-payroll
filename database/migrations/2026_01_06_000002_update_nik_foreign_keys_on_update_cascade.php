<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Lembur: previously had FK without onUpdate; add onUpdate cascade
        if (Schema::hasTable('lembur')) {
            Schema::table('lembur', function (Blueprint $table) {
                $table->dropForeign(['nik']);
                $table->foreign('nik')->references('nik')->on('karyawan')->onDelete('cascade')->onUpdate('cascade');
            });
        }

        // Presensi: keep onDelete cascade, add onUpdate cascade
        if (Schema::hasTable('presensi')) {
            Schema::table('presensi', function (Blueprint $table) {
                $table->dropForeign(['nik']);
                $table->foreign('nik')->references('nik')->on('karyawan')->onDelete('cascade')->onUpdate('cascade');
            });
        }

        // Izin: keep onDelete cascade, add onUpdate cascade
        if (Schema::hasTable('izin')) {
            Schema::table('izin', function (Blueprint $table) {
                $table->dropForeign(['nik']);
                $table->foreign('nik')->references('nik')->on('karyawan')->onDelete('cascade')->onUpdate('cascade');
            });
        }

        // Setjamkerja: keep onDelete cascade, add onUpdate cascade
        if (Schema::hasTable('setjamkerjas')) {
            Schema::table('setjamkerjas', function (Blueprint $table) {
                $table->dropForeign(['nik']);
                $table->foreign('nik')->references('nik')->on('karyawan')->onDelete('cascade')->onUpdate('cascade');
            });
        }

        // KPI Daily: keep cascade on delete, add onUpdate cascade
        if (Schema::hasTable('kpi_daily')) {
            Schema::table('kpi_daily', function (Blueprint $table) {
                $table->dropForeign(['nik']);
                $table->foreign('nik')->references('nik')->on('karyawan')->onDelete('cascade')->onUpdate('cascade');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('lembur')) {
            Schema::table('lembur', function (Blueprint $table) {
                $table->dropForeign(['nik']);
                $table->foreign('nik')->references('nik')->on('karyawan');
            });
        }

        if (Schema::hasTable('presensi')) {
            Schema::table('presensi', function (Blueprint $table) {
                $table->dropForeign(['nik']);
                $table->foreign('nik')->references('nik')->on('karyawan')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('izin')) {
            Schema::table('izin', function (Blueprint $table) {
                $table->dropForeign(['nik']);
                $table->foreign('nik')->references('nik')->on('karyawan')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('setjamkerjas')) {
            Schema::table('setjamkerjas', function (Blueprint $table) {
                $table->dropForeign(['nik']);
                $table->foreign('nik')->references('nik')->on('karyawan')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('kpi_daily')) {
            Schema::table('kpi_daily', function (Blueprint $table) {
                $table->dropForeign(['nik']);
                $table->foreign('nik')->references('nik')->on('karyawan')->onDelete('cascade');
            });
        }
    }
};
