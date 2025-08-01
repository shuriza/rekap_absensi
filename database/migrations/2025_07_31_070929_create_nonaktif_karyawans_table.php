<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
public function up()
{
    Schema::create('nonaktif_karyawans', function (Blueprint $table) {
        $table->id();
        $table->foreignId('karyawan_id')->constrained()->onDelete('cascade');
        $table->date('tanggal_awal');
        $table->date('tanggal_akhir');
        $table->timestamps();
    });
}


    public function down(): void
    {
        Schema::dropIfExists('nonaktif_karyawans');
    }
};
