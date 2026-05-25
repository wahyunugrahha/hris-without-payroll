<?php

namespace App\Models;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;

class Departemen extends Model
{
    use LogsActivity;

    protected $table = 'departemen';
    protected $primaryKey = 'kode_dept';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'kode_dept',
        'nama_dept',
    ];

    public function karyawans()
    {
        return $this->hasMany(Karyawan::class, 'kode_dept', 'kode_dept');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'kode_dept', 'kode_dept');
    }

    public function kpi()
    {
        return $this->hasMany(KPIMaster::class, 'kode_dept', 'kode_dept');
    }

    public function configurations()
    {
        return $this->hasMany(KonfigurasiJkDept::class, 'kode_dept', 'kode_dept');
    }

    public function holidays()
    {
        return $this->hasMany(HariLibur::class, 'kode_dept', 'kode_dept');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('audit');
    }
}
