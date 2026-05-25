<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KonfigurasiUmum extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];
}
