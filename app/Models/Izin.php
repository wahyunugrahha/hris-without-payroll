<?php

namespace App\Models;

use App\Models\Concerns\VisibleByCabang;
use App\Support\CutiDatesMeta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Izin extends Model
{
    use HasFactory, VisibleByCabang;

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

    public function scopeMilik(Builder $query, string $nik): Builder
    {
        return $query->where('nik', $nik);
    }

    /**
     * Belum diverifikasi admin: masih boleh diubah/dihapus karyawan.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status_approved', 0);
    }

    public function scopeJenis(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Cuti (c) & roster (r) bisa berisi tanggal yang tidak berurutan; jenis lain berupa rentang dari-sampai.
     */
    public static function isMultiDateStatus(?string $status): bool
    {
        return in_array($status, ['c', 'r'], true);
    }

    /**
     * @return list<string> semua tanggal (Y-m-d) yang diajukan
     */
    public function tanggalDiajukan(): array
    {
        if (self::isMultiDateStatus($this->status)) {
            $metaDates = CutiDatesMeta::parse($this->keterangan);
            if (! empty($metaDates)) {
                return $metaDates;
            }
        }

        return CutiDatesMeta::datesFromRange($this->tgl_izin_dari, $this->tgl_izin_sampai);
    }
}
