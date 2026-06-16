<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KPIMasterDetail extends Model
{
    protected $table = 'kpi_master_detail';
    protected $guarded = ['id'];

    public function kpiMaster()
    {
        return $this->belongsTo(KPIMaster::class, 'kode_master', 'kode_master');
    }

}
