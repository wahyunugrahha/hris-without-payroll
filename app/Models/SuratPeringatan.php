<?php

namespace App\Models;

use App\Models\Concerns\VisibleByCabang;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuratPeringatan extends Model
{
    use HasFactory, VisibleByCabang;

    protected $table = 'surat_peringatan';

    protected $fillable = [
        'nik',
        'level',
        'violation_type',
        'issued_at',
        'expires_at',
        'note',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'expires_at' => 'date',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'nik', 'nik');
    }

    /**
     * SP yang belum kedaluwarsa.
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->whereDate('expires_at', '>', now()->toDateString());
    }
}
