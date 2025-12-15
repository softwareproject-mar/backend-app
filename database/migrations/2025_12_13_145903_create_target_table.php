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
        Schema::create('target', function (Blueprint $table) {
            $table->string('ID_KS', 12);
            $table->string('TGL_TGT', 50);
            $table->integer('JLH_AGT_BR')->nullable();
            $table->double('STR_SP')->nullable();
            $table->string('SLD_SP', 50)->nullable();
            $table->double('STR_SW')->nullable();
            $table->string('SLD_SW', 50)->nullable();
            $table->string('STR_SS', 50)->nullable();
            $table->string('SLD_SS', 50)->nullable();
            $table->integer('STR_SHR')->nullable();
            $table->string('SLD_SHR', 50)->nullable();
            $table->integer('STR_SMD')->nullable();
            $table->string('SLD_SMD', 50)->nullable();
            $table->double('STR_SPD')->nullable();
            $table->string('SLD_SPD', 50)->nullable();
            $table->double('STR_SBJ')->nullable();
            $table->string('SLD_SBJ', 50)->nullable();
            $table->string('STR_SJP', 50)->nullable();
            $table->string('SLD_SJP', 50)->nullable();
            $table->double('STR_SRY')->nullable();
            $table->string('SLD_SRY', 50)->nullable();
            $table->double('STR_SKA')->nullable();
            $table->string('SLD_SKA', 50)->nullable();
            $table->string('STR_SRI', 50)->nullable();
            $table->string('SLD_SRI', 50)->nullable();
            $table->string('STR_SSD', 50)->nullable();
            $table->string('SLD_SSD', 50)->nullable();
            $table->integer('PCR_PJM')->nullable();
            $table->string('SLD_PJM', 50)->nullable();
            $table->integer('BNG_PJM')->nullable();
            $table->string('SLD_BNG', 50)->nullable();
            $table->integer('ASR_PKK')->nullable();
            $table->string('REK_SHR', 50)->nullable();
            $table->string('REK_SPD', 50)->nullable();
            $table->string('REK_SMD', 50)->nullable();
            $table->string('REK_SRY', 50)->nullable();
            $table->string('STF_SBJ', 50)->nullable();
            $table->string('STF_SJP', 50)->nullable();

            $table->primary(['ID_KS', 'TGL_TGT']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('target');
    }
};
