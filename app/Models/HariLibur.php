<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HariLibur extends Model
{
    protected $table = 'hari_libur';

    protected $fillable = [
        'tanggal_libur',
        'keterangan',
        'jenis_libur',
        'kode_cabang',
        'kode_dept',
    ];

    protected $casts = [
        'tanggal_libur' => 'date',
    ];

    // Helper method untuk check apakah tanggal tertentu adalah hari libur
    public static function isHariLibur($tanggal, $kode_cabang = null, $kode_dept = null)
    {
        $query = self::where('tanggal_libur', $tanggal);

        // Filter berdasarkan cabang
        if ($kode_cabang) {
            $query->where(function ($q) use ($kode_cabang) {
                $q->whereNull('kode_cabang')
                    ->orWhere('kode_cabang', '')
                    ->orWhereRaw("concat(',', kode_cabang, ',') ilike ?", ["%,{$kode_cabang},%"])
                    ->orWhere('kode_cabang', $kode_cabang);
            });
        } else {
            $query->whereNull('kode_cabang');
        }

        // Filter berdasarkan departemen
        if ($kode_dept) {
            $query->where(function ($q) use ($kode_dept) {
                $q->whereNull('kode_dept')
                    ->orWhere('kode_dept', '')
                    ->orWhereRaw("concat(',', kode_dept, ',') ilike ?", ["%,{$kode_dept},%"])
                    ->orWhere('kode_dept', $kode_dept);
            });
        } else {
            $query->whereNull('kode_dept');
        }

        return $query->exists();
    }
}
