<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the ip_addresses table with UUID primary keys, soft deletes,
     * and support for both IPv4 and IPv6 addresses.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('ip_addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index()->comment('Owner of the IP address (soft reference to users)');
            $table->string('ip_address')->index()->comment('The IP address (IPv4 or IPv6)');
            $table->string('label')->comment('Display name for the IP address');
            $table->text('comment')->nullable()->comment('Optional description or notes');
            $table->enum('type', ['ipv4', 'ipv6'])->comment('IP address version');
            $table->timestamps();
            $table->softDeletes();

            // Composite index for common query patterns
            $table->index(['user_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_addresses');
    }
};
