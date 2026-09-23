<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('surat_peringatan', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 20);
            $table->tinyInteger('level')->default(1)->comment('1=SP1, 2=SP2, 3=SP3');
            $table->string('violation_type', 20)->comment('late, absent');
            $table->date('issued_at');
            $table->date('expires_at');
            $table->text('note')->nullable();
            $table->timestamps();

            // Index composite untuk performa query anti-spam & eskalasi
            $table->index(['nik', 'expires_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('surat_peringatan');
    }
};
