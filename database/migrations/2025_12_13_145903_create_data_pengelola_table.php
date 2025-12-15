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
        Schema::create('data_pengelola', function (Blueprint $table) {
            $table->string('ID_PENG', 12)->primary();
            $table->string('NO_AGT', 15)->nullable();
            $table->integer('NO_SK')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_pengelola');
    }
};
