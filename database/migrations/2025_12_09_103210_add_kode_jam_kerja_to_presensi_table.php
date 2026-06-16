<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    private function hasForeignKeyOnColumn(string $table, string $column): bool
    {
        return DB::table('information_schema.table_constraints as tc')
            ->join('information_schema.key_column_usage as kcu', function ($join) {
                $join->on('tc.constraint_name', '=', 'kcu.constraint_name')
                    ->on('tc.table_schema', '=', 'kcu.table_schema');
            })
            ->where('tc.constraint_type', 'FOREIGN KEY')
            ->where('tc.table_schema', DB::raw('current_schema()'))
            ->where('tc.table_name', $table)
            ->where('kcu.column_name', $column)
            ->exists();
    }

    public function up(): void
    {
        if (!Schema::hasColumn('presensi', 'kode_jam_kerja')) {
            Schema::table('presensi', function (Blueprint $table) {
                $table->char('kode_jam_kerja', 4)->after('tgl_presensi')->nullable();
            });
        }

        if (! $this->hasForeignKeyOnColumn('presensi', 'kode_jam_kerja')) {
            Schema::table('presensi', function (Blueprint $table) {
                $table->foreign('kode_jam_kerja')
                    ->references('kode_jam_kerja')
                    ->on('jam_kerja')
                    ->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasForeignKeyOnColumn('presensi', 'kode_jam_kerja')) {
            Schema::table('presensi', function (Blueprint $table) {
                $table->dropForeign(['kode_jam_kerja']);
            });
        }

        if (Schema::hasColumn('presensi', 'kode_jam_kerja')) {
            Schema::table('presensi', function (Blueprint $table) {
                $table->dropColumn('kode_jam_kerja');
            });
        }
    }
};