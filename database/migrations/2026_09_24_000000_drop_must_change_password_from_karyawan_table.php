<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fitur wajib ganti password default dibatalkan (keputusan bisnis: password default 123456 boleh tetap dipakai).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('karyawan', 'must_change_password')) {
            Schema::table('karyawan', function (Blueprint $table) {
                $table->dropColumn('must_change_password');
            });
        }
    }

    public function down(): void
    {
        Schema::table('karyawan', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false)->after('password');
        });
    }
};
