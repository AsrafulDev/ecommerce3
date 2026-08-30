<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeds ONLY the default/required data — admin user, permissions, themes,
 * general settings, layouts, contacts, homepage sections, colors, sizes,
 * districts and coupons.
 *
 * It deliberately does NOT seed demo products / categories / brands /
 * banners / warranty demo data (those are created by DemoDataSeeder and
 * WarrantyDemoSeeder).
 *
 * Run with:
 *   php artisan migrate:fresh --seed --seeder="Database\Seeders\DefaultDatabaseSeeder"
 *   # or the shortcut command:
 *   php artisan migrate:fresh:default
 */
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
            DistrictSeeder::class,
            CouponSeeder::class,
            ShippingChargeSeeder::class,
        ]);

        $this->command->info('Default data seeded (admin + settings only). No demo products/categories/brands were created.');
    }
}
