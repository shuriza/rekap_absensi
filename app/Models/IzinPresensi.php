<?php
// app/Models/IzinPresensi.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IzinPresensi extends Model
{
    use HasFactory;
  protected $table = 'izin_presensi';
    protected $fillable = [
        'karyawan_id',
        'tipe_ijin',
        'tanggal_awal',
        'tanggal_akhir',
        'jenis_ijin',
        'berkas',
        'keterangan',
    ];


        // Tambahkan ini:
    protected $casts = [
        // Jika di database bertipe DATE, cukup 'date'.
        // Anda juga bisa langsung sertakan format:
        'tanggal_awal'  => 'date:d-m-Y',
        'tanggal_akhir' => 'date:d-m-Y',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }
}
