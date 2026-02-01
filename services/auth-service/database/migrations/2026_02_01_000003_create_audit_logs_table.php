<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the audit_logs table for immutable audit trail storage.
     * Tracks user authentication events and system activities.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->string('event_type')->comment('Type of event: login, logout, etc.');
            $table->string('entity_type')->comment('Affected entity type: User, Session, etc.');
            $table->uuid('entity_id')->nullable()->comment('Affected entity UUID');
            $table->json('metadata')->nullable()->comment('Additional context: IP, user_agent, timestamp');
            $table->string('session_id')->nullable()->comment('Session identifier for correlation');
            $table->timestamp('created_at')->useCurrent();

            // Indexes for efficient querying
            $table->index('user_id');
            $table->index('event_type');
            $table->index('entity_type');
            $table->index('session_id');
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
        Schema::dropIfExists('audit_logs');
    }
};
