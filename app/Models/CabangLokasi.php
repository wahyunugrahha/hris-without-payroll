<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CabangLokasi extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'cabang_lokasis';
    public $timestamps = false;

    protected $fillable = [
        'kode_cabang',
        'nama_lokasi',
        'latitude',
        'longitude',
        'radius',
        'aktif',
    ];

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
