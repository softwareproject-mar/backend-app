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
        Schema::table('kel_sah', function (Blueprint $table) {
            $table->foreign(['ID_KETUA'], 'FK_KEL_SAH_1')->references(['ID_KET'])->on('ketua_ks')->onUpdate('cascade')->onDelete('no action');
            $table->foreign(['ID_LO'], 'FK_KEL_SAH_2')->references(['ID_LO'])->on('data_lo')->onUpdate('cascade')->onDelete('no action');
            $table->foreign(['ID_SEK'], 'FK_KEL_SAH_3')->references(['ID_SEKRE'])->on('sekre_ks')->onUpdate('cascade')->onDelete('no action');
            $table->foreign(['ID_AO'], 'FK_KEL_SAH_4')->references(['ID_AO'])->on('data_ao')->onUpdate('cascade')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kel_sah', function (Blueprint $table) {
            $table->dropForeign('FK_KEL_SAH_1');
            $table->dropForeign('FK_KEL_SAH_2');
            $table->dropForeign('FK_KEL_SAH_3');
            $table->dropForeign('FK_KEL_SAH_4');
        });
    }
};
