<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;

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
        'nama',
        'departemen',
        'is_ob',
    ];

    /* -----------------------------------------------------------------
     |  Relasi
     |------------------------------------------------------------------*/

    /** Presensi harian pegawai (jam masuk–pulang) */
    public function absensi(): HasMany
    {
        return $this->hasMany(Absensi::class, 'karyawan_id');
    }

// Relasi untuk periode nonaktif terbaru
    public function nonaktif_terbaru(): HasOne
    {
        return $this->hasOne(NonaktifKaryawan::class)
                    ->latestOfMany('tanggal_awal');
    }

    // Turunkan status dari relasi
    public function getStatusAttribute(): string
    {
        if ($this->nonaktif_terbaru && Carbon::parse($this->nonaktif_terbaru->tanggal_akhir)->isFuture()) {
            return 'nonaktif';
        }
        return 'aktif';
    }

    // Untuk kompatibilitas dengan view: sedang_nonaktif
    public function getSedangNonaktifAttribute(): bool
    {
        return $this->status === 'nonaktif';
    }

    /** Izin presensi (cuti, sakit, dinas luar, dst.) */
    public function izins(): HasMany
    {
        return $this->hasMany(IzinPresensi::class, 'karyawan_id');
    }

    public function nonaktifPadaBulan(int $tahun, int $bulan): bool
    {
        if (!$this->nonaktif_terbaru) return false;

        $awal = Carbon::parse($this->nonaktif_terbaru->tanggal_awal);
        $akhir = Carbon::parse($this->nonaktif_terbaru->tanggal_akhir);

        $startBulan = Carbon::create($tahun, $bulan, 1);
        $endBulan = $startBulan->copy()->endOfMonth();

        return $awal <= $endBulan && $akhir >= $startBulan;
    }

}
