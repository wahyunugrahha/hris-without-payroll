<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KonfigurasiJkDeptDetail extends Model
{
    protected $table = 'konfigurasi_jk_dept_detail';

    public $incrementing = false;

    protected $primaryKey = ['kode_jk_dept', 'hari'];

    protected $keyType = 'string';

    public $timestamps = false;

    // Kolom yang dapat diisi secara massal
    protected $fillable = [
        'kode_jk_dept',
        'hari',
        'kode_jam_kerja',
    ];

    protected function setKeysForSaveQuery($query)
    {
        $keys = $this->getKeyName();
        if (! is_array($keys)) {
            return parent::setKeysForSaveQuery($query);
        }

        foreach ($keys as $keyName) {
            $query->where($keyName, '=', $this->getAttribute($keyName));
        }

        return $query;
    }

    //  Relasi BelongsTo (Many-to-One)
    public function konfigurasi(): BelongsTo
    {
        return $this->belongsTo(KonfigurasiJkDept::class, 'kode_jk_dept', 'kode_jk_dept');
    }

    public function jamKerja(): BelongsTo
    {
        return $this->belongsTo(JamKerja::class, 'kode_jam_kerja', 'kode_jam_kerja');
    }
}
