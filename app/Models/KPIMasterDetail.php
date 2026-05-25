<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class KPIMasterDetail extends Model
{
    use LogsActivity;

    protected $table = 'kpi_master_detail';
    protected $guarded = ['id'];

    public function kpiMaster()
    {
        return $this->belongsTo(KPIMaster::class, 'kode_master', 'kode_master');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('audit');
    }
}
