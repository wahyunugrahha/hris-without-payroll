<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KPIAtasanDailyDetail extends Model
{
    protected $table = 'kpi_atasan_daily_detail';

    protected $guarded = ['id'];

    public function atasanDaily()
    {
        return $this->belongsTo(KPIAtasanDaily::class, 'kpi_atasan_daily_id', 'id');
    }

    // Relasi ke master indikator pertanyaan atasan
    public function kpiMasterAtasan()
    {
        return $this->belongsTo(KPIMasterAtasan::class, 'kpi_master_atasan_id', 'id');
    }
}
