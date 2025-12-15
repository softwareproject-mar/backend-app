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
        Schema::create('data_penghasilan', function (Blueprint $table) {
            $table->string('NO_AGT', 15)->index('fk_data_penghasilan_1');
            $table->string('PENGHASILAN', 50)->nullable();
            $table->string('PENGELUARAN', 50)->nullable();
            $table->string('TGL_DATA', 50)->nullable();

            $table->primary(['NO_AGT']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_penghasilan');
    }
};
