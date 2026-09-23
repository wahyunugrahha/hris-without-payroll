<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bpjs_tk_requests', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 50)->index();
            $table->enum('status', ['pending', 'processed'])->default('pending')->index();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('processed_by', 100)->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        DB::table('permissions')
            ->where('guard_name', 'user')
            ->where('name', 'bpjs-tk-view-admin')
            ->update(['name' => 'bpjs-view-admin']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('permissions')
            ->where('guard_name', 'user')
            ->where('name', 'bpjs-view-admin')
            ->update(['name' => 'bpjs-tk-view-admin']);

        Schema::dropIfExists('bpjs_tk_requests');
    }
};
