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
        Schema::table('data_trs', function (Blueprint $table) {
            $table->foreign(['NO_AGT'], 'FK_DATA_TRS_1')->references(['NO_AGT'])->on('anggota')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_trs', function (Blueprint $table) {
            $table->dropForeign('FK_DATA_TRS_1');
        });
    }
};
