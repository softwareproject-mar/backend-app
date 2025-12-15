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
        Schema::create('anggota', function (Blueprint $table) {
            $table->string('NO_AGT', 15)->primary();
            $table->string('NAMA', 50)->nullable();
            $table->string('ID_KS', 12)->nullable()->index('fk_anggota_1');
            $table->string('ID_LO', 12)->nullable();
            $table->string('ID_AO', 12)->nullable();
            $table->string('ID_KS_ASL', 12)->nullable()->index('fk_anggota_2');
            $table->string('TGL_MTS', 50)->nullable();
            $table->string('TGL_AKTIF', 50)->nullable();
            $table->string('TGL_JA', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anggota');
    }
};
