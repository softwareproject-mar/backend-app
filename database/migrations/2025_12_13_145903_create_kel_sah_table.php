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
        Schema::create('kel_sah', function (Blueprint $table) {
            $table->string('ID_KEL', 12)->primary();
            $table->string('NAMA_KEL', 50)->nullable()->unique('unq1_kel_sah');
            $table->string('ID_KETUA', 12)->index('fk_kel_sah_1');
            $table->string('ID_SEK', 12)->nullable()->index('fk_kel_sah_3');
            $table->string('ID_LO', 12)->index('fk_kel_sah_2');
            $table->string('ID_AO', 12)->index('fk_kel_sah_4');
            $table->string('ALAMAT', 50)->nullable();
            $table->string('STAT', 50)->nullable();
            $table->string('TGL_STAT', 50)->nullable();
            $table->string('ID_PENGELOLA', 12)->nullable();

            $table->unique(['ID_SEK'], 'unq2_kel_sah');
            $table->unique(['ID_KETUA'], 'unq3_kel_sah');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kel_sah');
    }
};
