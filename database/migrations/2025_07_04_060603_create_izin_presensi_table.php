<?php
// database/migrations/2025_07_04_create_izin_presensi_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIzinPresensiTable extends Migration
{
    public function up()
    {
    Schema::create('izin_presensi', function (Blueprint $table) {
        $table->id();

        // Ganti pegawai_id jadi karyawan_id, dan constrained ke karyawans
        $table->foreignId('karyawan_id')
              ->constrained('karyawans')
              ->onDelete('cascade');

        $table->enum('tipe_ijin', ['PENUH','PARSIAL','TERLAMBAT','PULANG CEPAT','LAINNYA']);
        $table->date('tanggal_awal');
        $table->date('tanggal_akhir')->nullable();
        $table->string('jenis_ijin');
        $table->string('berkas')->nullable();
        $table->text('keterangan')->nullable();
        $table->timestamps();
    });
    }

    public function down()
    {
        Schema::dropIfExists('izin_presensi');
    }
}
