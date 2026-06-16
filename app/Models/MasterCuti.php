<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterCuti extends Model
{
    use HasFactory;

    protected $table = 'master_cuti';

    protected $primaryKey = 'kode_cuti';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;
    protected $fillable = [
        'kode_cuti',
        'nama_cuti',
        'jml_hari',
    ];

    protected $casts = [
        'jml_hari' => 'integer',
    ];

    public function izins()
    {
        return $this->hasMany(Izin::class, 'kode_cuti', 'kode_cuti');
    }

}