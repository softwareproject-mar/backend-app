<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satu ID_LO / ID_AO hanya boleh terpasang pada satu baris kel_sah (sama seperti ketua & sekretaris).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kel_sah', function (Blueprint $table) {
            $table->unique(['ID_LO'], 'unq_kel_sah_id_lo');
            $table->unique(['ID_AO'], 'unq_kel_sah_id_ao');
        });
    }

    public function down(): void
    {
        Schema::table('kel_sah', function (Blueprint $table) {
            $table->dropUnique('unq_kel_sah_id_lo');
            $table->dropUnique('unq_kel_sah_id_ao');
        });
    }
};
