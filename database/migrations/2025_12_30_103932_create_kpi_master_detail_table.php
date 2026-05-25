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
        Schema::create('kpi_master_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_master_id')->constrained('kpi_master')->cascadeOnDelete();

            $table->string('kode_kpi')->unique();
            $table->string('indikator');
            $table->decimal('bobot', 5, 2);
            $table->integer('target');
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_master_detail');
    }
};
