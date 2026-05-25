<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('izin', function (Blueprint $table) {
            $table->char('kode_cuti', 3)->after('status')->nullable();
            $table->foreign('kode_cuti')
                ->references('kode_cuti')
                ->on('master_cuti')
                ->onDelete('restrict');
            $table->char('status', 1)->comment('i: izin; s: sakit; c: cuti')->change();
        });
    }


    public function down(): void
    {
        Schema::table('izin', function (Blueprint $table) {
            $table->dropForeign(['kode_cuti']);
            $table->dropColumn('kode_cuti');
            $table->char('status', 1)->comment('i: izin; s: sakit')->change();
        });
    }
};