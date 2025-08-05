<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('karyawans', function (Blueprint $table) {
            $table->boolean('is_ob')->default(false)->after('departemen');
        });
    }

    public function down()
    {
        Schema::table('karyawans', function (Blueprint $table) {
            $table->dropColumn('is_ob');
        });
    }
};
