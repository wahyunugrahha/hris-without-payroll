<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KPIReport extends Model
{
    protected $table = 'kpi_report';

    protected $guarded = ['id'];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'nik', 'nik');
    }
}
