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
        Schema::create('data_trs', function (Blueprint $table) {
            $table->string('NO_AGT', 15)->index('fk_data_trs_1');
            $table->string('STR_SP', 50)->nullable();
            $table->string('STR_SW', 50)->nullable();
            $table->string('STR_SKA', 50)->nullable();
            $table->string('STR_SRI', 50)->nullable();
            $table->string('STR_SDK', 50)->nullable();
            $table->string('STR_PJM', 50)->nullable();
            $table->string('STR_BNG', 50)->nullable();
            $table->string('PJM_BARU', 50)->nullable();
            $table->string('STR_SHR', 50)->nullable();
            $table->string('STR_SBJ', 50)->nullable();
            $table->string('STR_SJP', 50)->nullable();
            $table->string('STR_SPD', 50)->nullable();
            $table->string('STR_SRY', 50)->nullable();
            $table->string('STR_SMD', 50)->nullable();
            $table->string('TGL_LAP', 50)->nullable();

            $table->primary(['NO_AGT']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_trs');
    }
};
