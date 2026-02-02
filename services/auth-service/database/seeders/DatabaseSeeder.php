<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Class DatabaseSeeder
 *
 * Main database seeder for the auth service.
 * Seeds roles and initial admin user.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
        ]);

        // Create initial super admin user
        $this->createSuperAdmin();

        $this->command->info('Database seeding completed successfully!');
    }

    /**
     * Create the initial super admin user.
     */
    protected function createSuperAdmin(): void
    {
        $adminEmail = 'admin@example.com';

        $user = User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'email' => $adminEmail,
                'password' => Hash::make('password'),
                'role' => 'super_admin',
            ]
        );

        // Assign super_admin role using Spatie
        $user->assignRole('super_admin');

        $this->command->info("Super admin user created/updated: {$adminEmail}");
        $this->command->warn('Default password: "password" - Change in production!');
    }
}
