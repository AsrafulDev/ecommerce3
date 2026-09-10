<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DefaultDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionTableSeeder::class,
            GeneralSettingSeeder::class,
            ThemeSeeder::class,
            LayoutSeeder::class,
            CreateAdminUserSeeder::class,
            ContactSeeder::class,
            HomepageSectionSeeder::class,
            ColorSeeder::class,
            SizeSeeder::class,
            CouponSeeder::class,
        ]);
    }
}
