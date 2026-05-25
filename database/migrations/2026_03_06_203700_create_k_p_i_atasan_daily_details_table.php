<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('kpi_atasan_daily_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kpi_atasan_daily_id');
            $table->unsignedBigInteger('kpi_master_atasan_id');
            
            $table->string('realisasi')->nullable(); 
            $table->decimal('score', 8, 2)->default(0);
            $table->boolean('is_checked')->default(false);
            $table->text('catatan')->nullable(); 
            $table->string('bukti_foto')->nullable(); 
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('kpi_atasan_daily_id', 'fk_atasan_daily_id')
                  ->references('id')->on('kpi_atasan_daily')->onDelete('cascade');
                  
            $table->foreign('kpi_master_atasan_id', 'fk_master_atasan_daily_id')
                  ->references('id')->on('kpi_master_atasan')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('kpi_atasan_daily_detail');
    }
};