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
        Schema::create('ibe_reports', function (Blueprint $table) {
            $table->integer('IBE$REPORT_ID')->nullable();
            $table->integer('IBE$REPORT_PARENT_ID')->nullable();
            $table->string('IBE$REPORT_NAME', 50)->nullable();
            $table->string('IBE$REPORT_SOURCE', 50)->nullable();
            $table->string('IBE$REPORT_RIGHTS', 50)->nullable();
            $table->integer('IBE$REPORT_IS_REPORT')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ibe_reports');
    }
};
