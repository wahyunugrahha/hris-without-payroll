<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JamKerja extends Model
{
    protected $table = 'jam_kerja';

    protected $primaryKey = 'kode_jam_kerja';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'kode_jam_kerja',
        'nama_jam_kerja',
        'awal_jam_masuk',
        'jam_masuk',
        'akhir_jam_masuk',
        'jam_pulang',
        'lintashari',
    ];

    public function presensis()
    {
        return $this->hasMany(Presensi::class, 'kode_jam_kerja', 'kode_jam_kerja');
    }

    public function deptDetails()
    {
        return $this->hasMany(KonfigurasiJkDeptDetail::class, 'kode_jam_kerja', 'kode_jam_kerja');
    }

    public function personalSchedules()
    {
        return $this->hasMany(Setjamkerja::class, 'kode_jam_kerja', 'kode_jam_kerja');
    }
}
