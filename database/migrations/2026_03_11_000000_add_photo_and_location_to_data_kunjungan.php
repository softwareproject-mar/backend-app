<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_kunjungan', function (Blueprint $table) {
            $table->string('FOTO_PATH', 255)->nullable()->after('JLH_PESERTA');
            $table->decimal('LATITUDE', 10, 7)->nullable()->after('FOTO_PATH');
            $table->decimal('LONGITUDE', 10, 7)->nullable()->after('LATITUDE');
        });
    }

    public function down(): void
    {
        Schema::table('data_kunjungan', function (Blueprint $table) {
            $table->dropColumn(['FOTO_PATH', 'LATITUDE', 'LONGITUDE']);
        });
    }
};
