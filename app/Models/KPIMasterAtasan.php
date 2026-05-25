<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class KPIMasterAtasan extends Model
{
    use LogsActivity;

    protected $table = 'kpi_master_atasan';
    protected $guarded = ['id'];

    public function master()
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
