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
        if (! Schema::hasColumn('users', 'kode_cabang')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('kode_cabang', 8)->nullable()->after('kode_dept');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'kode_cabang')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('kode_cabang');
            });
        }
    }
};
