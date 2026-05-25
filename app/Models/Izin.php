<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Izin extends Model
{
    use HasFactory;

    protected $table = 'izin';

    protected $primaryKey = 'kode_izin';

    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'kode_izin',
        'nik',
        'tgl_izin_dari',
        'tgl_izin_sampai',
        'status',
        'kode_cuti',
        'keterangan',
        'doc_sid',
        'status_approved',
        'status_decided_at',
        'catatan_ditolak',
    ];

    protected $casts = [
        'status_decided_at' => 'datetime',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'nik', 'nik');
    }

    public function masterCuti()
    {
        return $this->belongsTo(MasterCuti::class, 'kode_cuti', 'kode_cuti');
    }
}