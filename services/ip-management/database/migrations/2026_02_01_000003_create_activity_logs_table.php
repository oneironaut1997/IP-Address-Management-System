<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the activity_logs table for Spatie Activity Log.
     * Tracks all user actions for comprehensive audit trails.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('log_name')->nullable()->index()->comment('Category or context for the log entry');
            $table->text('description')->comment('Human-readable description of the activity');
            $table->uuid('subject_id')->nullable()->index()->comment('ID of the affected entity');
            $table->string('subject_type')->nullable()->comment('Class name of the affected entity');
            $table->uuid('causer_id')->nullable()->index()->comment('ID of the user who performed the action');
            $table->string('causer_type')->nullable()->comment('Class name of the causer (usually User)');
            $table->json('properties')->nullable()->comment('Additional data about the activity');
            $table->string('event')->nullable()->comment('Event type (e.g., created, updated, deleted)');
            $table->uuid('batch_uuid')->nullable()->comment('For grouping related activities');
            $table->timestamps();

            // Composite indexes for common query patterns
            $table->index(['log_name', 'subject_type', 'subject_id']);
            $table->index(['causer_type', 'causer_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
