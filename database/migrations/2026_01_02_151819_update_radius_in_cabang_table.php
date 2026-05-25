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
        Schema::table('cabang', function (Blueprint $table) {
            // Mengubah tipe data 'radius' menjadi integer
            // Integer bisa menampung angka hingga 2.147.483.647
            $table->integer('radius')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cabang', function (Blueprint $table) {
            // Kembalikan ke smallInteger jika di-rollback
            $table->smallInteger('radius')->change();
        });
    }
};