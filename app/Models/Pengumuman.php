<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengumuman extends Model
{
    use HasFactory;

    protected $table = 'pengumuman';

    protected $guarded = ['id'];

    // Scope untuk mengambil pengumuman yang aktif hari ini
    public function scopeAktif($query)
    {
        $today = date('Y-m-d');

        return $query->where('tanggal_mulai', '<=', $today)
            ->where('tanggal_selesai', '>=', $today)
            ->where('is_active', 1);
    }
}
