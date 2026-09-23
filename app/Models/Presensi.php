<?php

namespace App\Models;

use App\Models\Concerns\VisibleByCabang;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Presensi extends Model
{
    use HasFactory, VisibleByCabang;

    protected $table = 'presensi';

    protected $fillable = [
        'nik',
        'tgl_presensi',
        'kode_jam_kerja',
        'jam_in',
        'jam_out',
        'foto_in',
        'foto_out',
        'lokasi_in',
        'lokasi_out',
        'status',
        'kejanggalan',
    ];

    protected $casts = [
        'tgl_presensi' => 'date',
    ];

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'nik', 'nik');
    }

    public function jamKerja(): BelongsTo
    {
        return $this->belongsTo(JamKerja::class, 'kode_jam_kerja', 'kode_jam_kerja');
    }
}
