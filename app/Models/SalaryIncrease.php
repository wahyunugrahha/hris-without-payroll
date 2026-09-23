<?php

namespace App\Models;

use App\Models\Concerns\VisibleByCabang;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryIncrease extends Model
{
    use HasFactory, VisibleByCabang;

    protected $fillable = [
        'nik',
        'persentase',
        'tanggal_pengajuan',
        'status',
        'approved_at',
        'approved_by',
        'catatan',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'nik', 'nik');
    }
}
