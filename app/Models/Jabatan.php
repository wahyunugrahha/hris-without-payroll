<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class Jabatan extends Model
{
    protected $table = 'jabatan';

    protected $fillable = [
        'nama_jabatan',
        'role_id',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function karyawans()
    {
        return $this->hasMany(Karyawan::class, 'jabatan_id', 'id');
    }

    public function kpiMaster()
    {
        return $this->hasOne(KPIMaster::class, 'jabatan_id', 'id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'jabatan_id', 'id');
    }
}
