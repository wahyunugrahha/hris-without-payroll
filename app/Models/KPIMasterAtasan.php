<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KPIMasterAtasan extends Model
{
    protected $table = 'kpi_master_atasan';
    protected $guarded = ['id'];

    public function master()
    {
        return $this->belongsTo(KPIMaster::class, 'kode_master', 'kode_master');
    }

}
