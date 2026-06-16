<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cabang extends Model
{
    use HasFactory;

    protected $table = 'cabang';
    protected $primaryKey = 'kode_cabang';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = [
        'kode_cabang',
        'nama_cabang',
        'lokasi_kantor',
        'radius',
    ];

    public function lokasis()
    {
        return $this->hasMany(CabangLokasi::class, 'kode_cabang', 'kode_cabang');
    }

    public function kpi()
    {
        return $this->hasMany(KPIMaster::class, 'kode_cabang', 'kode_cabang');
    }

    public function karyawans()
    {
        return $this->hasMany(Karyawan::class, 'kode_cabang', 'kode_cabang');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'kode_cabang', 'kode_cabang');
    }

    public function jkDepts()
    {
        return $this->hasMany(KonfigurasiJkDept::class, 'kode_cabang', 'kode_cabang');
    }

    public function holidays()
    {
        return $this->hasMany(HariLibur::class, 'kode_cabang', 'kode_cabang');
    }
}