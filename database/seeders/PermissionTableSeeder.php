<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionTableSeeder extends Seeder
{
    /**
     * All known permission strings used across admin controllers.
     * Keep this in sync with PermissionController::ALL_PERMISSIONS.
     */
    const ALL_PERMISSIONS = [
        // Banner
        'banner-list', 'banner-create', 'banner-edit', 'banner-delete',
        // Banner Category
        'banner-category-list', 'banner-category-create', 'banner-category-edit', 'banner-category-delete',
        // Category
        'category-list', 'category-create', 'category-edit', 'category-delete',
        // Child Category
        'childcategory-list', 'childcategory-create', 'childcategory-edit', 'childcategory-delete',
        // Color
        'color-list', 'color-create', 'color-edit', 'color-delete',
        // Contact
        'contact-list', 'contact-create', 'contact-edit', 'contact-delete',
        // Contact Message
        'contact-message-list', 'contact-message-edit', 'contact-message-delete',
        // Layout
        'layout-list', 'layout-create', 'layout-edit', 'layout-delete',
        // Page
        'page-list', 'page-create', 'page-edit', 'page-delete',
        // Permission
        'permission-list', 'permission-create', 'permission-edit', 'permission-delete',
        // Product
        'product-list', 'product-create', 'product-edit', 'product-delete',
        // Review
        'review-list', 'review-create', 'review-edit', 'review-delete',
        // Role
        'role-list', 'role-create', 'role-edit', 'role-delete',
        // Setting
        'setting-list', 'setting-create', 'setting-edit', 'setting-delete',
        // Shipping
        'shipping-list', 'shipping-create', 'shipping-edit', 'shipping-delete',
        // Size
        'size-list', 'size-create', 'size-edit', 'size-delete',
        // Social
        'social-list', 'social-create', 'social-edit', 'social-delete',
        // Sub Category
        'subcategory-list', 'subcategory-create', 'subcategory-edit', 'subcategory-delete',
        // Theme
        'theme-list', 'theme-create', 'theme-edit', 'theme-delete',
    ];

    /**
     * Run the database seeds.
     */
    public function run()
    {
        foreach (self::ALL_PERMISSIONS as $permission) {
            Permission::firstOrCreate([
                'name'       => $permission,
                'guard_name' => 'admin',
            ]);
        }

        $this->command->info('Seeded ' . count(self::ALL_PERMISSIONS) . ' permissions.');
    }
}
