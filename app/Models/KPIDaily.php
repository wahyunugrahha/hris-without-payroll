<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KPIDaily extends Model
{
    protected $table = 'kpi_daily';

    protected $fillable = [
        'nik',
        'tanggal',
        'status',
        'alasan_reject',
        'approve_atasan', 
        'approve_atasan_at',
        'approve_hr',
        'approve_hr_at'
    ];

    protected $casts = [
        'tanggal'     => 'date',
        'approve_atasan_at' => 'datetime',
        'approve_hr_at'     => 'datetime',
    ];

    public function kpiDailyDetail()
    {
        return $this->hasMany(KPIDailyDetail::class, 'kpi_daily_id');
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'nik', 'nik');
    }

    public function kpiDailyExtra()
    {
        return $this->hasMany(KPIDailyExtra::class, 'kpi_daily_id');
    }
}