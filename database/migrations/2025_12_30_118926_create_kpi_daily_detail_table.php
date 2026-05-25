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
        Schema::create('kpi_daily_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_daily_id')->constrained('kpi_daily')->cascadeOnDelete();
            $table->foreignId('kpi_master_detail_id')
                ->constrained('kpi_master_detail')->cascadeOnDelete();

            $table->boolean('is_checked')->default(false);
            $table->decimal('bobot_tercapai', 6, 2)->default(0);
            $table->decimal('target_tercapai', 6, 2)->default(0);
            $table->string('catatan')->nullable();

            $table->timestamps();

            $table->unique(['kpi_daily_id', 'kpi_master_detail_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_daily_detail');
    }
};
