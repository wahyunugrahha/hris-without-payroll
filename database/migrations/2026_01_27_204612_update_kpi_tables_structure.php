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
        // 1. UPDATE TABLE: kpi_master
        Schema::table('kpi_master', function (Blueprint $table) {
            if (!Schema::hasColumn('kpi_master', 'jabatan_id')) {
                $table->foreignId('jabatan_id')->nullable()->constrained('jabatan')->onDelete('cascade');
            }
        });

        // 2. UPDATE TABLE: kpi_master_detail
        Schema::table('kpi_master_detail', function (Blueprint $table) {
            if (!Schema::hasColumn('kpi_master_detail', 'input_by')) {
                $table->string('input_by', 255)->nullable()->default('self')->comment('system, self, atasan');
            }
        });

        // 3. UPDATE TABLE: kpi_daily
        Schema::table('kpi_daily', function (Blueprint $table) {
            if (!Schema::hasColumn('kpi_daily', 'approve_atasan')) {
                $table->timestamp('approve_atasan')->nullable()->comment('Kapan atasan ACC');
            }
            if (!Schema::hasColumn('kpi_daily', 'approve_hr')) {
                $table->timestamp('approve_hr')->nullable()->comment('Kapan HR ACC');
            }

            if (Schema::hasColumn('kpi_daily', 'bobot_tercapai')) {
                $table->dropColumn('bobot_tercapai');
            }
        });

        DB::table('kpi_daily')
            ->where('status', 'approved')
            ->update(['status' => 'approved_by_hr']);
        DB::statement("ALTER TABLE kpi_daily DROP CONSTRAINT IF EXISTS kpi_daily_status_check");
        DB::statement("
            ALTER TABLE kpi_daily 
            ADD CONSTRAINT kpi_daily_status_check 
            CHECK (status IN ('draft', 'submitted', 'approved_by_atasan', 'approved_by_hr', 'rejected'))
        ");

        // 4. UPDATE TABLE: kpi_daily_detail
        Schema::table('kpi_daily_detail', function (Blueprint $table) {
            if (!Schema::hasColumn('kpi_daily_detail', 'realisasi')) {
                $table->text('realisasi')->nullable(); 
            }
            if (!Schema::hasColumn('kpi_daily_detail', 'score')) {
                $table->decimal('score', 8, 2)->nullable();
            }
            if (!Schema::hasColumn('kpi_daily_detail', 'bukti_foto')) {
                $table->string('bukti_foto', 255)->nullable();
            }

            $columnsToDrop = [];
            if (Schema::hasColumn('kpi_daily_detail', 'bobot_tercapai')) $columnsToDrop[] = 'bobot_tercapai';
            if (Schema::hasColumn('kpi_daily_detail', 'target_tercapai')) $columnsToDrop[] = 'target_tercapai';
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        // 5. CREATE TABLE: kpi_daily_extra
        if (!Schema::hasTable('kpi_daily_extra')) {
            Schema::create('kpi_daily_extra', function (Blueprint $table) {
                $table->id();
                $table->foreignId('kpi_daily_id')->constrained('kpi_daily')->onDelete('cascade');
                $table->string('indikator_tambahan');
                $table->text('catatan')->nullable();
                $table->timestamps();
            });
        }

        // 6. CREATE TABLE: kpi_monthly
        if (!Schema::hasTable('kpi_monthly')) {
            Schema::create('kpi_monthly', function (Blueprint $table) {
                $table->id();
                
                $table->string('nik', 50); 

                $table->string('periode_bulan', 2); 
                $table->year('periode_tahun');
                $table->decimal('avg_score', 8, 2)->default(0);
                $table->string('status')->nullable();
                $table->timestamps();

                // Foreign Key Definition
                $table->foreign('nik')
                    ->references('nik')
                    ->on('karyawan')
                    ->onUpdate('cascade')
                    ->onDelete('cascade'); 
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('kpi_daily')
            ->whereIn('status', ['approved_by_atasan', 'approved_by_hr'])
            ->update(['status' => 'approved']);

        DB::statement("ALTER TABLE kpi_daily DROP CONSTRAINT IF EXISTS kpi_daily_status_check");
        
        DB::statement("
            ALTER TABLE kpi_daily 
            ADD CONSTRAINT kpi_daily_status_check 
            CHECK (status IN ('draft', 'submitted', 'approved', 'rejected'))
        ");

        Schema::table('kpi_daily', function (Blueprint $table) {
            $table->decimal('bobot_tercapai', 6, 2)->nullable();
            $table->dropColumn(['approve_atasan', 'approve_hr']);
        });

        Schema::table('kpi_daily_detail', function (Blueprint $table) {
            $table->decimal('bobot_tercapai', 6, 2)->nullable();
            $table->decimal('target_tercapai', 6, 2)->nullable();
            $table->dropColumn(['realisasi', 'score', 'bukti_foto']);
        });
        
        Schema::dropIfExists('kpi_daily_extra');
        Schema::dropIfExists('kpi_monthly');
    }
};
