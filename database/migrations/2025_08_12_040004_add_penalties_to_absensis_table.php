<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('absensis', function (Blueprint $t) {
            // menit "kedispilinan" harian
            $t->unsignedSmallInteger('late_minutes')->nullable()->after('keterangan');   // telat masuk (menit)
            $t->unsignedSmallInteger('early_minutes')->nullable()->after('late_minutes'); // pulang cepat (menit)
            $t->unsignedSmallInteger('penalty_minutes')->default(0)->after('early_minutes'); // total penalti per-hari
            $t->unsignedSmallInteger('work_minutes')->nullable()->after('penalty_minutes');  // durasi kerja riil (opsional)
            $t->string('rule_label', 40)->nullable()->after('work_minutes'); // label aturan hari itu (default_senin, ramadhan_jumat, dst.)
        });

        // (opsional) pastikan unik per karyawan+tanggal (hapus jika sudah ada di migration lama)
        // Schema::table('absensis', function (Blueprint $t) {
        //     $t->unique(['karyawan_id','tanggal'], 'absensis_karyawan_tanggal_unique');
        // });
    }

    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $t) {
            $t->dropColumn(['late_minutes','early_minutes','penalty_minutes','work_minutes','rule_label']);
        });

        // (opsional)
        // Schema::table('absensis', function (Blueprint $t) {
        //     $t->dropUnique('absensis_karyawan_tanggal_unique');
        // });
    }
};
