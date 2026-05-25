<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hari_libur', function (Blueprint $table) {
            if (!Schema::hasColumn('hari_libur', 'kode_dept')) {
                $table->string('kode_dept')->nullable()->after('kode_cabang');
            }
        });

        DB::statement("ALTER TABLE hari_libur ALTER COLUMN jenis_libur TYPE varchar(20) USING jenis_libur::varchar");
        DB::statement("ALTER TABLE hari_libur ALTER COLUMN jenis_libur SET DEFAULT 'nasional'");
        DB::statement("UPDATE hari_libur SET jenis_libur = 'lokal' WHERE jenis_libur = 'lainnya'");
        DB::statement("ALTER TABLE hari_libur DROP CONSTRAINT IF EXISTS hari_libur_jenis_libur_check");
        DB::statement("ALTER TABLE hari_libur ADD CONSTRAINT hari_libur_jenis_libur_check CHECK (jenis_libur IN ('nasional','cuti_bersama','lokal'))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE hari_libur DROP CONSTRAINT IF EXISTS hari_libur_jenis_libur_check");
        DB::statement("ALTER TABLE hari_libur ADD CONSTRAINT hari_libur_jenis_libur_check CHECK (jenis_libur IN ('nasional','cuti_bersama','lainnya'))");

        Schema::table('hari_libur', function (Blueprint $table) {
            if (Schema::hasColumn('hari_libur', 'kode_dept')) {
                $table->dropColumn('kode_dept');
            }
        });
    }
};
