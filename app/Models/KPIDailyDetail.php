<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class KPIDailyDetail extends Model
{
    protected $table = 'kpi_daily_detail';

    protected $fillable = [
        'kpi_daily_id',
        'kpi_master_detail_id',
        'realisasi',
        'score',
        'is_checked',
        'catatan',
        'bukti_foto',
    ];

    protected $casts = [
        'score' => 'float',
    ];

    public function getBuktiFotoUrlAttribute()
    {
        if ($this->bukti_foto && Storage::exists($this->bukti_foto)) {
            return asset_v('storage/'.$this->bukti_foto);
        }

        return null;
    }

    public function kpiDaily()
    {
        return $this->belongsTo(KPIDaily::class, 'kpi_daily_id');
    }

    public function kpiMasterDetail()
    {
        return $this->belongsTo(KPIMasterDetail::class, 'kpi_master_detail_id');
    }
}
