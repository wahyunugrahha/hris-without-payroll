<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lembur extends Model
{
    protected $table = 'lembur';

    protected $fillable = [
        'kode_lembur',
        'nik',
        'tanggal_lembur',
        'pekerjaan',
        'tempat',
        'jam_mulai',
        'jam_selesai',
        'jam_selesai_awal',
        'total_jam',
        'foto_masuk',
        'foto_keluar',
        'keterangan',
        'status_approved',
        'update_count',
        'last_update_at',
    ];

    protected $casts = [
        'tanggal_lembur' => 'date',
        'total_jam' => 'decimal:2',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'nik', 'nik');
    }
}
