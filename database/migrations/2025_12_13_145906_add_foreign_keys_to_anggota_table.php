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
        Schema::table('anggota', function (Blueprint $table) {
            $table->foreign(['ID_KS'], 'FK_ANGGOTA_1')->references(['ID_KEL'])->on('kel_sah')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['ID_KS_ASL'], 'FK_ANGGOTA_2')->references(['ID_KEL'])->on('kel_sah')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('anggota', function (Blueprint $table) {
            $table->dropForeign('FK_ANGGOTA_1');
            $table->dropForeign('FK_ANGGOTA_2');
        });
    }
};
