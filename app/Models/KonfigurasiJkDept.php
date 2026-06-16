<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KonfigurasiJkDept extends Model
{
    protected $table = 'konfigurasi_jk_dept';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'kode_jk_dept';

    protected $fillable = [
        'kode_jk_dept',
        'kode_cabang',
        'kode_dept',
    ];

    //  Relasi One-to-Many
    public function details(): HasMany
    {
        return $this->hasMany(KonfigurasiJkDeptDetail::class, 'kode_jk_dept', 'kode_jk_dept');
    }

    //  Relasi BelongsTo (Many-to-One)
    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'kode_cabang', 'kode_cabang');
    }

    public function departemen(): BelongsTo
    {
        return $this->belongsTo(Departemen::class, 'kode_dept', 'kode_dept');
    }

}