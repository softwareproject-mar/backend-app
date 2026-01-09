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
        Schema::create('id_sequences', function (Blueprint $table) {
            $table->string('entity_type', 50)->primary();
            $table->char('kode_role', 1);
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

                $table->index('entity_type', 'idx_entity_type');
        });

        // NO DATA INSERTION IN MIGRATION - will be handled by test setup or manual seeding
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('id_sequences');
    }
};
