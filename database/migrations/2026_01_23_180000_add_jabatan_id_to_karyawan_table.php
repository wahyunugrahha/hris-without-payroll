<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('karyawan', function (Blueprint $table) {
            if (!Schema::hasColumn('karyawan', 'jabatan_id')) {
                $table->unsignedBigInteger('jabatan_id')->nullable()->after('jabatan');
                $table->index('jabatan_id');
            }
        });

        // Add FK if not exists
        if (!DB::select("SELECT 1 FROM pg_constraint WHERE conname = 'karyawan_jabatan_id_foreign'")) {
            Schema::table('karyawan', function (Blueprint $table) {
                $table->foreign('jabatan_id')->references('id')->on('jabatan')->nullOnDelete();
            });
        }

        // Backfill: cocokkan nama_jabatan ke kolom jabatan lama
        $mapping = DB::table('jabatan')->pluck('id', 'nama_jabatan');
        foreach ($mapping as $namaJabatan => $jabatanId) {
            DB::table('karyawan')
                ->whereNull('jabatan_id')
                ->where('jabatan', $namaJabatan)
                ->update(['jabatan_id' => $jabatanId]);
        }
    }

    public function down(): void
    {
        Schema::table('karyawan', function (Blueprint $table) {
            $table->dropForeign(['jabatan_id']);
            $table->dropIndex(['jabatan_id']);
            $table->dropColumn('jabatan_id');
        });
    }
};
