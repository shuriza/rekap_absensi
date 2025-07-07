<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Karyawan extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama', 'departemen'
    ];

    public function absensi()
    {
        return $this->hasMany(Absensi::class, 'karyawan_id');
    }
}
