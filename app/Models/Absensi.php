<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    use HasFactory;

    protected $fillable = [
    'karyawan_id','tanggal','jam_masuk','jam_pulang','keterangan',
    'late_minutes','early_minutes','penalty_minutes','work_minutes','rule_label',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }

    protected $table = 'absensis';        // atau nama tabel Anda

    protected $casts = [
    'tanggal'         => 'date',
    'late_minutes'    => 'integer',
    'early_minutes'   => 'integer',
    'penalty_minutes' => 'integer',
    'work_minutes'    => 'integer',
    ];

}
