<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = ['tanggal', 'keterangan'];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
    ];
}
