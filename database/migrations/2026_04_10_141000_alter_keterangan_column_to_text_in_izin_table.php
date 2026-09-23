<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('izin') || ! Schema::hasColumn('izin', 'keterangan')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE izin ALTER COLUMN keterangan TYPE TEXT');

            return;
        }

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE izin MODIFY keterangan TEXT NULL');

            return;
        }

        if ($driver === 'sqlite') {
            // SQLite does not enforce VARCHAR length strictly; no change required.
            return;
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('izin') || ! Schema::hasColumn('izin', 'keterangan')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE izin ALTER COLUMN keterangan TYPE VARCHAR(255)');

            return;
        }

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE izin MODIFY keterangan VARCHAR(255) NULL');

            return;
        }

        if ($driver === 'sqlite') {
            // No rollback action for SQLite.
            return;
        }
    }
};
