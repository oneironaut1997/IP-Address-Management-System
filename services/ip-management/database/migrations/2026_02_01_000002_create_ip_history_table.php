<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the ip_history table for tracking changes to IP addresses.
     * Stores old and new values as JSON for complete audit trail.
     */
    public function up(): void
    {
        Schema::create('ip_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ip_address_id')
                ->constrained('ip_addresses')
                ->onDelete('cascade')
                ->comment('Reference to the IP address that was modified');
            $table->uuid('modified_by')->index()->comment('User who made the change (soft reference)');
            $table->json('old_values')->nullable()->comment('Previous state of the IP record');
            $table->json('new_values')->nullable()->comment('New state of the IP record');
            $table->enum('action', ['created', 'updated', 'deleted'])->comment('Type of change performed');
            $table->timestamp('created_at')->useCurrent();

            // Indexes for common query patterns
            $table->index(['ip_address_id', 'created_at']);
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_history');
    }
};
