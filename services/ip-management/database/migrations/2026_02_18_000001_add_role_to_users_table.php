<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds role column to users table and modifies ID to be UUID-compatible
     * to match the auth-service user model.
     */
    public function up(): void
    {
        // Add role column to existing users table
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['regular', 'super_admin'])->default('regular')->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
