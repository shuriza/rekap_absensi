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

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }
}
