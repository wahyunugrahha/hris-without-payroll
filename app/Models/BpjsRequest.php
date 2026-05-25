<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BpjsRequest extends Model
{
    use HasFactory;

    protected $table = 'bpjs_tk_requests';

    protected $fillable = [
        'nik',
        'status',
        'requested_at',
        'processed_at',
        'processed_by',
        'catatan',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'nik', 'nik');
    }
}
