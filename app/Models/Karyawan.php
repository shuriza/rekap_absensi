<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Karyawan extends Model
{
    use HasFactory;

    /* -----------------------------------------------------------------
     |  Tabel & Kolom
     |------------------------------------------------------------------*/
    // Jika nama tabel di database bukan “karyawans”, buka baris di bawah:
    // protected $table = 'karyawan';

    /** Kolom yang boleh mass-assign */
    protected $fillable = [
        'nip',
        'nama',
        'jabatan',
        'departemen',
    ];

    /* -----------------------------------------------------------------
     |  Relasi
     |------------------------------------------------------------------*/

    /** Presensi harian pegawai (jam masuk–pulang) */
    public function absensi(): HasMany
    {
        return $this->hasMany(Absensi::class, 'karyawan_id');
    }

    /** Izin presensi (cuti, sakit, dinas luar, dst.) */
    public function izins(): HasMany
    {
        return $this->hasMany(IzinPresensi::class, 'karyawan_id');
    }
}
