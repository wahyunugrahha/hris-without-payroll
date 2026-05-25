<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menambahkan foreign key constraint pada users.kode_cabang
     * Ketika cabang dihapus, kode_cabang user akan di-set ke NULL (setNullOnDelete)
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('kode_cabang')
                ->references('kode_cabang')
                ->on('cabang')
                ->cascadeOnUpdate()
                ->nullOnDelete(); // Ketika cabang dihapus, set kode_cabang = NULL
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeignIfExists(['kode_cabang']);
        });
    }
};

