<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CabangLokasi extends Model
{
    use HasFactory;

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

}
