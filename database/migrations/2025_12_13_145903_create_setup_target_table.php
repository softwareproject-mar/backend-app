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
        Schema::create('setup_target', function (Blueprint $table) {
            $table->string('ID', 50)->primary();
            $table->string('STR_SP', 50)->nullable();
            $table->string('SLD_SP', 50)->nullable();
            $table->string('STR_SW', 50)->nullable();
            $table->string('SLD_SW', 50)->nullable();
            $table->string('STR_SS', 50)->nullable();
            $table->string('SLD_SS', 50)->nullable();
            $table->string('STR_SHR', 50)->nullable();
            $table->string('SLD_SHR', 50)->nullable();
            $table->string('STR_SMD', 50)->nullable();
            $table->string('SLD_SMD', 50)->nullable();
            $table->string('STR_SPD', 50)->nullable();
            $table->string('SLD_SPD', 50)->nullable();
            $table->string('STR_SBJ', 50)->nullable();
            $table->string('SLD_SBJ', 50)->nullable();
            $table->string('STR_SJP', 50)->nullable();
            $table->string('SLD_SJP', 50)->nullable();
            $table->string('STR_SRY', 50)->nullable();
            $table->string('SLD_SRY', 50)->nullable();
            $table->string('STR_SKA', 50)->nullable();
            $table->string('SLD_SKA', 50)->nullable();
            $table->string('STR_SRI', 50)->nullable();
            $table->string('SLD_SRI', 50)->nullable();
            $table->string('STR_SSD', 50)->nullable();
            $table->string('SLD_SSD', 50)->nullable();
            $table->string('PCR_PJM', 50)->nullable();
            $table->string('SLD_PJM', 50)->nullable();
            $table->string('BNG_PJM', 50)->nullable();
            $table->string('SLD_BNG', 50)->nullable();
            $table->string('ASR_PKK', 50)->nullable();
            $table->string('REK_SHR', 50)->nullable();
            $table->string('REK_SPD', 50)->nullable();
            $table->string('REK_SMD', 50)->nullable();
            $table->string('REK_SRY', 50)->nullable();
            $table->string('STF_SBJ', 50)->nullable();
            $table->string('STF_SJP', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setup_target');
    }
};
