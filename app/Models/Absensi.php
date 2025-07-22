<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    use HasFactory;

    protected $fillable = [
        'karyawan_id', 'tanggal', 'jam_masuk', 'jam_pulang','keterangan'
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }

    protected $table = 'absensis';        // atau nama tabel Anda

    protected $casts = [
        'tanggal'    => 'date:Y-m-d',     // ⬅️ wajib supaya $presensi->tanggal jadi Carbon
        'jam_masuk'  => 'datetime:H:i:s',
        'jam_pulang' => 'datetime:H:i:s',
    ];

}
