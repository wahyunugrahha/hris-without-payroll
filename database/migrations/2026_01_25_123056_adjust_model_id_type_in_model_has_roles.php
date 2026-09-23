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
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropForeign(['model_id']);
        });

        DB::statement('ALTER TABLE model_has_roles ALTER COLUMN model_id TYPE VARCHAR(50) USING model_id::varchar');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE model_has_roles ALTER COLUMN model_id TYPE BIGINT USING model_id::bigint');

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->foreign('model_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
