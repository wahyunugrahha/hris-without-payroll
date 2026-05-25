<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Jabatan extends Model
{
    use LogsActivity;

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('audit');
    }
}
