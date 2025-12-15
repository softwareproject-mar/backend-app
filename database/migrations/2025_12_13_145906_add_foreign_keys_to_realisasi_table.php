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
        Schema::table('realisasi', function (Blueprint $table) {
            $table->foreign(['ID_KS'], 'FK_REALISASI_1')->references(['ID_KEL'])->on('kel_sah')->onUpdate('cascade')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('realisasi', function (Blueprint $table) {
            $table->dropForeign('FK_REALISASI_1');
        });
    }
};
