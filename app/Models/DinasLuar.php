<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DinasLuar extends Model
{
    use HasFactory;

    protected $table = 'dinas_luar';

    protected $fillable = [
        'nik',
        'tgl_mulai',
        'tgl_selesai',
        'alasan',
        'dasar_perjalanan',
        'transportasi',
        'dana_diajukan',
        'lokasi_tujuan',
        'keterangan',
        'status_acc',
        'catatan_approval',
        'approved_by',
        'approved_at'
    ];

    protected $casts = [
        'tgl_mulai' => 'date',
        'tgl_selesai' => 'date',
        'approved_at' => 'datetime'
    ];

    /**
     * Get the karyawan that owns the dinas luar
     */
    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'nik', 'nik');
    }

    /**
     * Get the approver of the dinas luar
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }

    /**
     * Scope untuk dinas luar yang sedang aktif
     */
    public function scopeAktif($query)
    {
        return $query->where('status_acc', 'acc')
            ->whereDate('tgl_mulai', '<=', now())
            ->whereDate('tgl_selesai', '>=', now());
    }

    /**
     * Scope untuk dinas luar yang menunggu approval
     */
    public function scopeMenunggu($query)
    {
        return $query->where('status_acc', 'menunggu');
    }

    /**
     * Cek apakah karyawan sedang dalam periode dinas luar yang acc
     */
    public function scopeForKaryawan($query, $nik, $tanggal = null)
    {
        if ($tanggal === null) {
            $tanggal = now()->toDateString();
        }

        return $query->where('nik', $nik)
            ->where('status_acc', 'acc')
            ->whereDate('tgl_mulai', '<=', $tanggal)
            ->whereDate('tgl_selesai', '>=', $tanggal);
    }
}
