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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            
            // User Information
            $table->unsignedBigInteger('user_id');
            $table->string('user_name');
            
            // Activity Details
            $table->string('resource_type', 100);
            $table->string('resource_id')->nullable();
            $table->enum('action_type', ['create', 'update', 'delete']);
            
            // Description & Status
            $table->text('description');
            $table->enum('status', ['success', 'failed'])->default('success');
            $table->text('error_message')->nullable();
            
            // Data Snapshot (JSON)
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            
            // Metadata
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            // Timestamp
            $table->timestamp('created_at')->useCurrent();
            
            // Indexes
            $table->index('user_id');
            $table->index(['resource_type', 'resource_id']);
            $table->index('action_type');
            $table->index('status');
            $table->index('created_at');
            
            // Foreign Key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
