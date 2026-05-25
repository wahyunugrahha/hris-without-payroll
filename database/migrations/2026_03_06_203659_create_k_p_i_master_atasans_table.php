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
        Schema::create('kpi_master_atasan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_master');
            $table->text('indikator');
            $table->decimal('bobot_atasan', 5, 2)->default(0);
            $table->decimal('target_atasan', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('kpi_master_atasan');
    }
};
