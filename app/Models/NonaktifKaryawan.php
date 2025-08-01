<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NonaktifKaryawan extends Model
{
    protected $fillable = ['karyawan_id', 'tanggal_awal', 'tanggal_akhir'];

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class);
    }
}
