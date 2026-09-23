<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departemen', function (Blueprint $table) {
            $table->char('kode_dept', 3)->primary();
            $table->string('nama_dept', 100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departemen');
    }
};
