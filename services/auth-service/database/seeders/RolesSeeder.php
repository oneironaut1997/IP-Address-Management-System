<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Class RolesSeeder
 *
 * Seeds the initial roles for the application.
 *
 * @package Database\Seeders
 */
class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates the default roles:
     * - regular: Standard user with limited permissions
     * - super_admin: Full system access
     *
     * @return void
     */
    public function run(): void
    {
        // Create regular user role for web guard (default)
        Role::firstOrCreate(
            ['name' => 'regular', 'guard_name' => 'web'],
            ['name' => 'regular']
        );

        // Create super admin role for web guard (default)
        Role::firstOrCreate(
            ['name' => 'super_admin', 'guard_name' => 'web'],
            ['name' => 'super_admin']
        );

        $this->command->info('Roles created successfully: regular, super_admin');
    }
}
