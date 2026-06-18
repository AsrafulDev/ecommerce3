<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Clear cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles for different guards
        // Admin role (uses 'admin' guard)
        Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'admin'],
            ['name' => 'admin', 'guard_name' => 'admin']
        );

        // Customer role (uses 'customer' guard)
        Role::firstOrCreate(
            ['name' => 'customer', 'guard_name' => 'customer'],
            ['name' => 'customer', 'guard_name' => 'customer']
        );

        $this->command->info('Roles created successfully!');
        $this->command->info('- Admin role (admin guard)');
        $this->command->info('- Customer role (customer guard)');
    }
}
