<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class KPIMaster extends Model
{
    use LogsActivity;

    protected $table = 'kpi_master';
    protected $guarded = ['id'];

    public function kpiMasterDetail()
    {
        return $this->hasMany(KPIMasterDetail::class, 'kode_master', 'kode_master');
    }

    public function kpiMasterAtasan()
    {
        return $this->hasMany(KPIMasterAtasan::class, 'kode_master', 'kode_master');
    }

    public function jabatan()
    {
        return $this->belongsTo(Jabatan::class, 'jabatan_id');
    }

    public function departemen()
    {
        return $this->belongsTo(Departemen::class, 'kode_dept', 'kode_dept');
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'kode_cabang', 'kode_cabang');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('audit');
    }
}
