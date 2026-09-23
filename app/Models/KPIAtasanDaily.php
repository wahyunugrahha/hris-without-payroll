<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KPIAtasanDaily extends Model
{
    protected $table = 'kpi_atasan_daily';

    protected $guarded = ['id'];

    public function details()
    {
        return $this->hasMany(KPIAtasanDailyDetail::class, 'kpi_atasan_daily_id', 'id');
    }

    // Karyawan yang dinilai
    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'nik', 'nik');
    }

    // Atasan yang menilai (menggunakan alias 'penilai')
    public function penilai()
    {
        return $this->belongsTo(Karyawan::class, 'input_atasan', 'nik');
    }
}
