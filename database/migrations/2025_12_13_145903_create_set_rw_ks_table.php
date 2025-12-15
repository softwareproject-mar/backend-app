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
        Schema::create('set_rw_ks', function (Blueprint $table) {
            $table->integer('NO_URT')->primary();
            $table->double('RW_KINJ_AGT')->nullable();
            $table->double('RW_STR_SW')->nullable();
            $table->double('RW_ASR_PJM')->nullable();
            $table->double('RW_STR_SPN')->nullable();
            $table->double('RW_PCR_PJM')->nullable();
            $table->double('RW_AGT_BARU')->nullable();
            $table->double('RW_AGT_BR_NON_REF')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('set_rw_ks');
    }
};
