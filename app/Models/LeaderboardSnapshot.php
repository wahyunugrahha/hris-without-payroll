<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaderboardSnapshot extends Model
{
    use HasFactory;

    protected $table = 'leaderboard_snapshots';

    protected $fillable = [
        'date', 'kode_cabang', 'rank', 'nik', 'nama_lengkap', 'jam_in', 'jam_out', 'jadwal_masuk', 'jadwal_pulang', 'points', 'point_details',
    ];

    protected $casts = [
        'point_details' => 'array',
    ];
}
