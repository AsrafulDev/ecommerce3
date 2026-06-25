<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Seed roles first
        $this->call([
            RoleSeeder::class,
        ]);

        // Seed theme system data
        $this->call([
            PermissionTableSeeder::class,
            GeneralSettingSeeder::class,
            ThemeSeeder::class,
            LayoutSeeder::class,
            CreateAdminUserSeeder::class,
            ContactSeeder::class,
            HomepageSectionSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
