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
        Schema::create('data_jlh_keluarga', function (Blueprint $table) {
            $table->string('NO_AGT', 15)->index('fk_data_jlh_keluarga_1');
            $table->string('JLH_AGT_KEL', 50)->nullable();
            $table->string('TGL', 50)->nullable();

            $table->primary(['NO_AGT']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_jlh_keluarga');
    }
};
