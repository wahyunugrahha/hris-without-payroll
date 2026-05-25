<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RekapBulanan extends Model
{
    use HasFactory;

    protected $fillable = [
        'nik',
        'kode_cabang',
        'bulan',
        'tahun',
        'total_poin',
        'total_poin_kpi',
        'total_izin_sakit',
        'bonus_bulanan',
        'avg_jam_masuk'
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'nik', 'nik');
    }
}
