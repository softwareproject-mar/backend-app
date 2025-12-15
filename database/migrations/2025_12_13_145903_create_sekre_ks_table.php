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
        Schema::create('sekre_ks', function (Blueprint $table) {
            $table->string('ID_SEKRE', 12)->primary();
            $table->string('NO_AGT', 15)->unique('unq1_sekre_ks');
            $table->string('NAMA', 50)->nullable();
            $table->string('STAT', 50)->nullable();
            $table->string('TGL_STAT', 50)->nullable();
            $table->integer('NO_SK')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sekre_ks');
    }
};
