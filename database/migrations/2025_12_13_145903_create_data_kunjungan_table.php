<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('data_kunjungan', function (Blueprint $table) {
            $table->integer('NO_URT')->primary();
            $table->string('ID_LO', 12)->nullable();
            $table->string('NO_AGT', 15)->nullable();
            $table->string('ID_KEL_SAH', 12)->nullable();
            $table->string('TGL_KUN', 50)->nullable();
            $table->string('KEGIATAN', 50)->nullable();
            $table->string('ID_PIC', 50)->nullable();
            $table->integer('JLH_PESERTA')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_kunjungan');
    }
};
