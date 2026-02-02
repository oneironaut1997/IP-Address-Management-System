<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the user_sessions table for tracking active authentication sessions.
     * Stores JWT token metadata, IP addresses, and user agents for audit purposes.
     */
    public function up(): void
    {
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->string('token_jti')->comment('JWT Token ID for refresh token tracking');
            $table->string('ip_address')->nullable()->comment('Client IP address');
            $table->text('user_agent')->nullable()->comment('Browser/device information');
            $table->timestamp('last_activity')->nullable()->comment('Last known activity timestamp');
            $table->timestamp('expires_at')->comment('Session expiration timestamp');
            $table->timestamps();

            // Index for faster lookup by user
            $table->index('user_id');
            // Index for token lookup during refresh
            $table->index('token_jti');
            // Index for cleaning up expired sessions
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
