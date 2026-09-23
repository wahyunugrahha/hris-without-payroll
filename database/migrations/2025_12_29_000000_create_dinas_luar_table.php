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
        Schema::create('dinas_luar', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 50); // Foreign key ke karyawan
            $table->date('tgl_mulai');
            $table->date('tgl_selesai');
            $table->string('alasan');
            $table->text('dasar_perjalanan')->nullable();
            $table->enum('transportasi', ['darat', 'laut', 'udara'])->nullable();
            $table->decimal('dana_diajukan', 15, 2)->nullable();
            $table->text('lokasi_tujuan')->nullable();
            $table->text('keterangan')->nullable();
            $table->enum('status_acc', ['menunggu', 'acc', 'tolak'])->default('menunggu');
            $table->text('catatan_approval')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('nik')
                ->references('nik')
                ->on('karyawan')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Index untuk performa query
            $table->index(['nik', 'tgl_mulai', 'tgl_selesai']);
            $table->index(['status_acc']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dinas_luar');
    }
};
