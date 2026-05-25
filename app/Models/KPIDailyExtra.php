<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KPIDailyExtra extends Model
{
    protected $table = 'kpi_daily_extra';

    protected $fillable = [
        'kpi_daily_id',
        'indikator_tambahan',
        'catatan',
        'score'
    ];

    public function kpiDaily()
    {
        return $this->belongsTo(KPIDaily::class, 'kpi_daily_id');
    }
}