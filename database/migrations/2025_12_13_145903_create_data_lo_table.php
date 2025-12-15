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
        Schema::create('data_lo', function (Blueprint $table) {
            $table->string('ID_LO', 12)->primary();
            $table->string('NO_AGT', 15)->nullable()->index('fk_data_lo_1');
            $table->string('ID_TP', 50)->nullable();
            $table->string('NAMA', 50)->nullable();
            $table->string('STAT', 50)->nullable();
            $table->string('TGL_STAT', 50)->nullable();

            $table->unique(['NO_AGT'], 'unq1_data_lo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_lo');
    }
};
