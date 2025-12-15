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
        Schema::create('data_ao', function (Blueprint $table) {
            $table->string('ID_AO', 12)->primary();
            $table->string('NO_AGT', 15)->nullable()->unique('unq1_data_ao');
            $table->string('NAMA', 50)->nullable();
            $table->string('STAT', 50)->nullable();
            $table->string('TGL_STAT', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_ao');
    }
};
