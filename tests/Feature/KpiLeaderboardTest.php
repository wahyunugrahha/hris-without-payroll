<?php

namespace Tests\Feature;

use App\Models\Karyawan;
use App\Models\KPIDaily;
use App\Models\KPIDailyExtra;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KpiLeaderboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_hanya_kpi_yang_dikirim_atau_disetujui_masuk_leaderboard(): void
    {
        DB::table('cabang')->insert(['kode_cabang' => 'CBG1', 'nama_cabang' => 'Cabang 1', 'lokasi_kantor' => '0,0', 'radius' => 100]);

        foreach (['draft' => '1001', 'rejected' => '1002', 'submitted' => '1003', 'approved_by_hr' => '1004'] as $status => $nik) {
            Karyawan::create([
                'nik' => $nik, 'nama_lengkap' => "K$nik", 'nama_panggilan' => "K$nik", 'no_hp' => '0',
                'password' => 'x', 'kode_cabang' => 'CBG1', 'status_aktif' => Karyawan::STATUS_AKTIF,
            ]);
            $kpi = KPIDaily::create(['nik' => $nik, 'tanggal' => now()->toDateString(), 'status' => $status]);
            KPIDailyExtra::create(['kpi_daily_id' => $kpi->id, 'indikator_tambahan' => 'x', 'score' => 10]);
        }

        $this->artisan('leaderboard:snapshot-kpi')->assertSuccessful();

        $this->assertEqualsCanonicalizing(
            ['1003', '1004'],
            DB::table('kpi_leaderboard_snapshots')->where('date', now()->toDateString())->pluck('nik')->all()
        );
    }
}
