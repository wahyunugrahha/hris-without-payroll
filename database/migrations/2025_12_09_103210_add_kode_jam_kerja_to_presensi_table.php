<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('presensi', function (Blueprint $table) {
            // Tambahkan kolom kode_jam_kerja
            $table->char('kode_jam_kerja', 4)->after('tgl_presensi')->nullable();

            // Tambahkan foreign key constraint
            $table->foreign('kode_jam_kerja')
                ->references('kode_jam_kerja')
                ->on('jam_kerja')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('presensi', function (Blueprint $table) {
            // Hapus foreign key terlebih dahulu
            $table->dropForeign(['kode_jam_kerja']);

            // Hapus kolom
            $table->dropColumn('kode_jam_kerja');
        });
    }
};