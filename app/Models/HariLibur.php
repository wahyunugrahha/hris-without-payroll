<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    private const SEMUA_CABANG = ['', 'Semua Cabang'];

    private const SEMUA_DEPT = ['', 'Semua Departemen'];

    /**
     * Hari libur yang berlaku untuk karyawan di cabang & departemen tertentu:
     * libur umum, libur cabangnya, libur departemennya, atau libur khusus cabang+departemennya.
     * Kolom kode_cabang/kode_dept null (atau '' / 'Semua ...' dari data lama) berarti berlaku untuk semua.
     */
    public function scopeBerlakuUntuk(Builder $query, ?string $kodeCabang, ?string $kodeDept): Builder
    {
        $semuaCabang = fn (Builder $q) => $q->whereNull('kode_cabang')->orWhereIn('kode_cabang', self::SEMUA_CABANG);
        $semuaDept = fn (Builder $q) => $q->whereNull('kode_dept')->orWhereIn('kode_dept', self::SEMUA_DEPT);
        // Data lama bisa menyimpan beberapa kode dipisah koma.
        $cabangIni = fn (Builder $q) => $q->where('kode_cabang', $kodeCabang)
            ->orWhereRaw("concat(',', kode_cabang, ',') like ?", ["%,{$kodeCabang},%"]);
        $deptIni = fn (Builder $q) => $q->where('kode_dept', $kodeDept)
            ->orWhereRaw("concat(',', kode_dept, ',') like ?", ["%,{$kodeDept},%"]);

        return $query->where(function (Builder $q) use ($kodeCabang, $kodeDept, $semuaCabang, $semuaDept, $cabangIni, $deptIni) {
            $q->where(fn ($w) => $w->where($semuaCabang)->where($semuaDept));

            if (! empty($kodeCabang)) {
                $q->orWhere(fn ($w) => $w->where($cabangIni)->where($semuaDept));
            }

            if (! empty($kodeDept)) {
                $q->orWhere(fn ($w) => $w->where($deptIni)->where($semuaCabang));
            }

            if (! empty($kodeCabang) && ! empty($kodeDept)) {
                $q->orWhere(fn ($w) => $w->where($cabangIni)->where($deptIni));
            }
        });
    }

    /**
     * @return list<string> tanggal libur (Y-m-d) dalam rentang, untuk cabang & departemen tersebut
     */
    public static function tanggalBerlaku(?string $kodeCabang, ?string $kodeDept, $dari, $sampai): array
    {
        return self::whereBetween('tanggal_libur', [$dari, $sampai])
            ->berlakuUntuk($kodeCabang, $kodeDept)
            ->pluck('tanggal_libur')
            ->map(fn ($tanggal) => date('Y-m-d', strtotime($tanggal)))
            ->unique()
            ->values()
            ->all();
    }

    public static function isHariLibur($tanggal, $kode_cabang = null, $kode_dept = null): bool
    {
        return self::where('tanggal_libur', $tanggal)->berlakuUntuk($kode_cabang, $kode_dept)->exists();
    }
}
