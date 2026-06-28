<?php

namespace App\Helpers;

class PresetData
{
    /**
     * Get all available presets with metadata.
     */
    public static function all(): array
    {
        return [
            'gadget-fashion-grocery' => [
                'name'        => 'Gadget + Fashion + Grocery',
                'slug'        => 'gadget-fashion-grocery',
                'description' => 'Multi-category shop selling gadgets, fashion items, electronics, and grocery products.',
                'live_url'    => 'https://ecommerce1.creativedesign.com.bd/',
                'color'       => '#0d6efd',
                'icon'        => 'mdi-store',
            ],
            'electronics' => [
                'name'        => 'Electronics Shop',
                'slug'        => 'electronics',
                'description' => 'Pure electronics & technology store with gadgets, laptops, phones, and accessories.',
                'live_url'    => 'https://ecommerce2.creativedesign.com.bd/',
                'color'       => '#dc2626',
                'icon'        => 'mdi-laptop',
            ],
            'food-grocery' => [
                'name'        => 'Natural Food & Grocery',
                'slug'        => 'food-grocery',
                'description' => 'Organic food, natural products, grocery items, and health foods store.',
                'live_url'    => 'https://ecommerce3.creativedesign.com.bd/',
                'color'       => '#16a34a',
                'icon'        => 'mdi-food-apple',
            ],
            'clothing-fashion' => [
                'name'        => 'Clothing Fashion',
                'slug'        => 'clothing-fashion',
                'description' => 'Apparel, fashion wear, accessories, and designer clothing collection.',
                'live_url'    => 'https://ecommerce6.creativedesign.com.bd/',
                'color'       => '#9333ea',
                'icon'        => 'mdi-hanger',
            ],
            'beauty' => [
                'name'        => 'Beauty Shop',
                'slug'        => 'beauty',
                'description' => 'Beauty products, skincare, makeup, hair care, and cosmetics store.',
                'live_url'    => 'https://ecommerce7.creativedesign.com.bd/',
                'color'       => '#ec4899',
                'icon'        => 'mdi-spray-bottle',
            ],
        ];
    }

    /**
     * Get data for a specific preset.
     * Loads from storage/app/demo-presets/{slug}/data.json first,
     * falls back to hardcoded preset methods.
     */
    public static function get(string $slug): ?array
    {
        $all = self::all();
        if (!isset($all[$slug])) return null;

        // Try loading from JSON preset file first
        $jsonPath = storage_path("app/demo-presets/{$slug}/data.json");
        if (file_exists($jsonPath)) {
            $data = json_decode(file_get_contents($jsonPath), true);
            if ($data && isset($data['meta'])) {
                return $data;
            }
        }

        // Fallback to hardcoded presets
        $method = 'preset' . str_replace('-', '', ucwords($slug, '-'));
        if (method_exists(self::class, $method)) {
            return self::$method();
        }
        return null;
    }

    /**
     * Get the filesystem path to a preset's data directory.
     */
    public static function path(string $slug): ?string
    {
        $path = storage_path("app/demo-presets/{$slug}");
        return is_dir($path) ? $path : null;
    }

    /**
     * Clean/reset the database — truncate all data tables.
     * Уses DB::statement to disable FK checks, then truncates.
     * Call from anywhere: PresetData::clean();
     */
    public static function clean(): void
    {
        $tables = [
            'categories', 'subcategories', 'childcategories', 'brands',
            'products', 'productimages', 'productcolors', 'productsizes',
            'product_variant_prices', 'product_wholesale_prices',
            'banners', 'banner_categories', 'blogs',
            'shipping_charges', 'reviews', 'orders', 'order_details',
            'payments', 'shippings', 'carts',
            'campaigns', 'campaign_product', 'campaign_reviews', 'coupons',
            'courierapis', 'incomplete_orders',
            'expenses', 'purchases', 'purchase_items', 'purchase_logs', 'expense_logs',
            'vendors', 'vendor_wallets', 'vendor_wallet_transactions', 'vendor_withdrawals',
            'suppliers', 'supplier_payments', 'complaints', 'contact_messages',
            'fund_transactions', 'fund_transaction_logs',
            'employees', 'employee_attendances', 'employee_leaves',
            'employee_salaries', 'employee_bonuses', 'employee_salary_payments',
            'refunds', 'newsletter_subscribers',
            'districts', 'ip_blocks', 'ecom_pixels', 'tiktok_pixels',
            'social_media', 'create_pages', 'order_statuses',
            'payment_gateways', 'sms_gateways', 'google_tag_managers',
            'seo_settings', 'ads_analytics_settings',
            'popups', 'cron_job_settings',
            'contact', 'contacts', 'colors', 'sizes',
            'digital_downloads', 'password_resets',
            'reseller_deposits', 'reseller_wallet_transactions', 'reseller_withdrawals',
            'reseller_landing_pages', 'reseller_landing_products',
            'reseller_landing_contact_messages', 'reseller_landing_newsletter_subscribers',
            'stolen_reports', 'facebook_capi_settings', 'facebook_page_settings',
            'wholesale_products', 'wholesale_product_images',
        ];

        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($tables as $table) {
            if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
                \Illuminate\Support\Facades\DB::table($table)->truncate();
            }
        }
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        \Illuminate\Support\Facades\Cache::flush();
    }

    /**
     * Get all presets with enriched metadata (including image counts).
     */
    public static function allWithDetails(): array
    {
        $presets = self::all();
        foreach ($presets as $slug => &$meta) {
            $presetPath = storage_path("app/demo-presets/{$slug}");
            $meta['has_data'] = file_exists($presetPath . '/data.json');
            $meta['has_screenshot'] = file_exists($presetPath . '/screenshot.jpg');
            if (is_dir($presetPath . '/images')) {
                $images = array_diff(scandir($presetPath . '/images'), ['.', '..']);
                $meta['image_count'] = count($images);
            } else {
                $meta['image_count'] = 0;
            }
        }
        return $presets;
    }

    // ============================================================
    // PRESET: Gadget + Fashion + Grocery (multi-type)
    // ============================================================
    private static function presetGadgetFashionGrocery(): array
    {
        $storeName = 'Gadget & Fashion Store';
        return [
            'meta' => self::all()['gadget-fashion-grocery'],
            'general_settings' => [
                'name'    => $storeName,
                'white_logo' => 'public/assets/images/CurlBazar.svg',
                'dark_logo'  => 'public/assets/images/CurlBazar.svg',
                'favicon'    => 'public/favicon.ico',
                'copyright'  => '© 2026 ' . $storeName . '. All rights reserved.',
                'show_all_products' => 1,
                'show_category_wise_products' => 1,
                'vendor_enabled' => 0,
                'reseller_enabled' => 0,
            ],
            'categories' => [
                ['name' => 'Smartphones', 'slug' => 'smartphones', 'parent_id' => 0, 'image' => 'public/demo/cat-smartphones.jpg'],
                ['name' => 'Laptops & PCs', 'slug' => 'laptops-pcs', 'parent_id' => 0, 'image' => 'public/demo/cat-laptops.jpg'],
                ['name' => 'Fashion Men', 'slug' => 'fashion-men', 'parent_id' => 0, 'image' => 'public/demo/cat-fashion-men.jpg'],
                ['name' => 'Fashion Women', 'slug' => 'fashion-women', 'parent_id' => 0, 'image' => 'public/demo/cat-fashion-women.jpg'],
                ['name' => 'Grocery & Food', 'slug' => 'grocery-food', 'parent_id' => 0, 'image' => 'public/demo/cat-grocery.jpg'],
                ['name' => 'Home Appliances', 'slug' => 'home-appliances', 'parent_id' => 0, 'image' => 'public/demo/cat-home-appliances.jpg'],
                ['name' => 'Sports & Fitness', 'slug' => 'sports-fitness', 'parent_id' => 0, 'image' => 'public/demo/cat-sports.jpg'],
                ['name' => 'Baby & Toys', 'slug' => 'baby-toys', 'parent_id' => 0, 'image' => 'public/demo/cat-baby.jpg'],
            ],
            'subcategories' => [
                ['cat' => 'smartphones', 'name' => 'Android Phones', 'slug' => 'android-phones'],
                ['cat' => 'smartphones', 'name' => 'iPhone', 'slug' => 'iphone'],
                ['cat' => 'laptops-pcs', 'name' => 'Gaming Laptops', 'slug' => 'gaming-laptops'],
                ['cat' => 'laptops-pcs', 'name' => 'Ultrabooks', 'slug' => 'ultrabooks'],
                ['cat' => 'fashion-men', 'name' => 'Shirts', 'slug' => 'shirts'],
                ['cat' => 'fashion-men', 'name' => 'Shoes', 'slug' => 'shoes'],
                ['cat' => 'fashion-women', 'name' => 'Dresses', 'slug' => 'dresses'],
                ['cat' => 'fashion-women', 'name' => 'Handbags', 'slug' => 'handbags'],
                ['cat' => 'grocery-food', 'name' => 'Rice & Grains', 'slug' => 'rice-grains'],
                ['cat' => 'grocery-food', 'name' => 'Beverages', 'slug' => 'beverages'],
                ['cat' => 'home-appliances', 'name' => 'Kitchen Appliances', 'slug' => 'kitchen-appliances'],
                ['cat' => 'home-appliances', 'name' => 'Air Coolers', 'slug' => 'air-coolers'],
                ['cat' => 'sports-fitness', 'name' => 'Gym Equipment', 'slug' => 'gym-equipment'],
                ['cat' => 'sports-fitness', 'name' => 'Cycling', 'slug' => 'cycling'],
                ['cat' => 'baby-toys', 'name' => 'Diapers', 'slug' => 'diapers'],
                ['cat' => 'baby-toys', 'name' => 'Educational Toys', 'slug' => 'educational-toys'],
            ],
            'brands' => ['Samsung', 'Apple', 'Xiaomi', 'HP', 'Dell', 'Nike', 'Adidas', 'Zara', 'H&M', 'Puma', 'LG', 'Bosch', 'Nestlé', 'Pepsi'],
            'products' => [
                ['name' => 'Samsung Galaxy S24 Ultra', 'cat' => 'smartphones', 'brand' => 'Samsung', 'price' => 129999, 'old' => 139999, 'stock' => 50],
                ['name' => 'iPhone 15 Pro Max', 'cat' => 'smartphones', 'brand' => 'Apple', 'price' => 149999, 'old' => 159999, 'stock' => 30],
                ['name' => 'Xiaomi 14 Pro', 'cat' => 'smartphones', 'brand' => 'Xiaomi', 'price' => 79999, 'old' => 89999, 'stock' => 100],
                ['name' => 'HP Pavilion Laptop 15', 'cat' => 'laptops-pcs', 'brand' => 'HP', 'price' => 65000, 'old' => 72000, 'stock' => 25],
                ['name' => 'Dell XPS 13', 'cat' => 'laptops-pcs', 'brand' => 'Dell', 'price' => 115000, 'old' => 125000, 'stock' => 15],
                ['name' => 'Nike Air Max 270', 'cat' => 'fashion-men', 'brand' => 'Nike', 'price' => 15999, 'old' => 18999, 'stock' => 200],
                ['name' => 'Adidas Ultraboost 23', 'cat' => 'fashion-men', 'brand' => 'Adidas', 'price' => 18999, 'old' => 22000, 'stock' => 150],
                ['name' => 'Zara Slim Fit Blazer', 'cat' => 'fashion-men', 'brand' => 'Zara', 'price' => 8999, 'old' => 12000, 'stock' => 80],
                ['name' => 'Puma Running Shoes', 'cat' => 'fashion-men', 'brand' => 'Puma', 'price' => 12999, 'old' => 15999, 'stock' => 180],
                ['name' => 'Zara Summer Dress', 'cat' => 'fashion-women', 'brand' => 'Zara', 'price' => 5999, 'old' => 7500, 'stock' => 120],
                ['name' => 'H&M Cotton T-Shirt', 'cat' => 'fashion-women', 'brand' => 'H&M', 'price' => 1499, 'old' => 1999, 'stock' => 500],
                ['name' => 'Nike Handbag Premium', 'cat' => 'fashion-women', 'brand' => 'Nike', 'price' => 8999, 'old' => 11000, 'stock' => 60],
                ['name' => 'Premium Basmati Rice 5kg', 'cat' => 'grocery-food', 'brand' => 'Nestlé', 'price' => 650, 'old' => 800, 'stock' => 1000],
                ['name' => 'Pepsi 2L (Pack of 6)', 'cat' => 'grocery-food', 'brand' => 'Pepsi', 'price' => 480, 'old' => 600, 'stock' => 2000],
                ['name' => 'LG Microwave Oven', 'cat' => 'home-appliances', 'brand' => 'LG', 'price' => 15999, 'old' => 18999, 'stock' => 60],
                ['name' => 'Bosch Blender Pro', 'cat' => 'home-appliances', 'brand' => 'Bosch', 'price' => 5499, 'old' => 6999, 'stock' => 300],
                ['name' => 'Home Gym Set 20kg', 'cat' => 'sports-fitness', 'brand' => 'Nike', 'price' => 8999, 'old' => 11000, 'stock' => 40],
                ['name' => 'Baby Stroller X100', 'cat' => 'baby-toys', 'brand' => 'Adidas', 'price' => 15999, 'old' => 18999, 'stock' => 35],
                ['name' => 'LEGO City Police Set', 'cat' => 'baby-toys', 'brand' => 'Puma', 'price' => 4999, 'old' => 5999, 'stock' => 90],
            ],
            'blogs' => [
                ['title' => 'Top Gadgets of 2026', 'short_desc' => 'Discover the best gadgets available this year.', 'views' => 1500],
                ['title' => 'Fashion Trends This Season', 'short_desc' => 'Stay ahead with our fashion guide.', 'views' => 900],
            ],
            'shipping_charges' => [
                ['name' => 'Inside Dhaka', 'amount' => 60],
                ['name' => 'Outside Dhaka', 'amount' => 110],
            ],
        ];
    }

    // ============================================================
    // PRESET: Electronics Shop
    // ============================================================
    private static function presetElectronics(): array
    {
        $storeName = 'Electronics Shop BD';
        return [
            'meta' => self::all()['electronics'],
            'general_settings' => [
                'name'    => $storeName,
                'white_logo' => 'public/assets/images/CurlBazar.svg',
                'dark_logo'  => 'public/assets/images/CurlBazar.svg',
                'favicon'    => 'public/favicon.ico',
                'copyright'  => '© 2026 ' . $storeName . '. All rights reserved.',
                'show_all_products' => 1,
                'show_category_wise_products' => 1,
                'vendor_enabled' => 0,
                'reseller_enabled' => 0,
            ],
            'categories' => [
                ['name' => 'Mobile Phones', 'slug' => 'mobile-phones', 'parent_id' => 0, 'image' => 'public/demo/cat-mobile.jpg'],
                ['name' => 'Laptops', 'slug' => 'laptops', 'parent_id' => 0, 'image' => 'public/demo/cat-laptop.jpg'],
                ['name' => 'Tablets & iPads', 'slug' => 'tablets', 'parent_id' => 0, 'image' => 'public/demo/cat-tablet.jpg'],
                ['name' => 'Headphones & Audio', 'slug' => 'headphones-audio', 'parent_id' => 0, 'image' => 'public/demo/cat-audio.jpg'],
                ['name' => 'Cameras', 'slug' => 'cameras', 'parent_id' => 0, 'image' => 'public/demo/cat-camera.jpg'],
                ['name' => 'Smart Watches', 'slug' => 'smart-watches', 'parent_id' => 0, 'image' => 'public/demo/cat-watch.jpg'],
                ['name' => 'Gaming', 'slug' => 'gaming', 'parent_id' => 0, 'image' => 'public/demo/cat-gaming.jpg'],
                ['name' => 'Computer Accessories', 'slug' => 'accessories', 'parent_id' => 0, 'image' => 'public/demo/cat-accessories.jpg'],
            ],
            'subcategories' => [
                ['cat' => 'mobile-phones', 'name' => 'Android', 'slug' => 'android'],
                ['cat' => 'mobile-phones', 'name' => 'iPhone', 'slug' => 'iphone'],
                ['cat' => 'laptops', 'name' => 'Gaming Laptops', 'slug' => 'gaming-laptops'],
                ['cat' => 'laptops', 'name' => 'Business Laptops', 'slug' => 'business-laptops'],
                ['cat' => 'tablets', 'name' => 'iPad', 'slug' => 'ipad'],
                ['cat' => 'tablets', 'name' => 'Android Tablets', 'slug' => 'android-tablets'],
                ['cat' => 'headphones-audio', 'name' => 'Wireless', 'slug' => 'wireless'],
                ['cat' => 'headphones-audio', 'name' => 'Wired', 'slug' => 'wired'],
                ['cat' => 'cameras', 'name' => 'DSLR', 'slug' => 'dslr'],
                ['cat' => 'cameras', 'name' => 'Mirrorless', 'slug' => 'mirrorless'],
                ['cat' => 'smart-watches', 'name' => 'Apple Watch', 'slug' => 'apple-watch'],
                ['cat' => 'smart-watches', 'name' => 'Samsung Watch', 'slug' => 'samsung-watch'],
                ['cat' => 'gaming', 'name' => 'Console', 'slug' => 'console'],
                ['cat' => 'gaming', 'name' => 'PC Gaming', 'slug' => 'pc-gaming'],
                ['cat' => 'accessories', 'name' => 'Keyboards', 'slug' => 'keyboards'],
                ['cat' => 'accessories', 'name' => 'Mice', 'slug' => 'mice'],
            ],
            'brands' => ['Samsung', 'Apple', 'Sony', 'LG', 'HP', 'Dell', 'Canon', 'Nikon', 'Bose', 'JBL', 'Xiaomi', 'OnePlus', 'Asus', 'Lenovo'],
            'products' => [
                ['name' => 'Samsung Galaxy S24 Ultra', 'cat' => 'mobile-phones', 'brand' => 'Samsung', 'price' => 129999, 'old' => 139999, 'stock' => 50],
                ['name' => 'iPhone 15 Pro Max', 'cat' => 'mobile-phones', 'brand' => 'Apple', 'price' => 149999, 'old' => 159999, 'stock' => 30],
                ['name' => 'OnePlus 12', 'cat' => 'mobile-phones', 'brand' => 'OnePlus', 'price' => 89999, 'old' => 99999, 'stock' => 80],
                ['name' => 'Dell XPS 13', 'cat' => 'laptops', 'brand' => 'Dell', 'price' => 115000, 'old' => 125000, 'stock' => 15],
                ['name' => 'HP Pavilion 15', 'cat' => 'laptops', 'brand' => 'HP', 'price' => 65000, 'old' => 72000, 'stock' => 25],
                ['name' => 'Asus ROG Gaming Laptop', 'cat' => 'laptops', 'brand' => 'Asus', 'price' => 185000, 'old' => 210000, 'stock' => 10],
                ['name' => 'Apple iPad Air M2', 'cat' => 'tablets', 'brand' => 'Apple', 'price' => 74999, 'old' => 79999, 'stock' => 40],
                ['name' => 'Samsung Galaxy Tab S9', 'cat' => 'tablets', 'brand' => 'Samsung', 'price' => 89999, 'old' => 95000, 'stock' => 35],
                ['name' => 'Sony WH-1000XM5', 'cat' => 'headphones-audio', 'brand' => 'Sony', 'price' => 29999, 'old' => 35000, 'stock' => 100],
                ['name' => 'JBL Flip 6 Speaker', 'cat' => 'headphones-audio', 'brand' => 'JBL', 'price' => 12999, 'old' => 15999, 'stock' => 150],
                ['name' => 'Canon EOS R50', 'cat' => 'cameras', 'brand' => 'Canon', 'price' => 85000, 'old' => 92000, 'stock' => 20],
                ['name' => 'Nikon Z50', 'cat' => 'cameras', 'brand' => 'Nikon', 'price' => 78000, 'old' => 85000, 'stock' => 18],
                ['name' => 'Apple Watch Series 9', 'cat' => 'smart-watches', 'brand' => 'Apple', 'price' => 59999, 'old' => 65000, 'stock' => 60],
                ['name' => 'Samsung Watch 6', 'cat' => 'smart-watches', 'brand' => 'Samsung', 'price' => 45999, 'old' => 52000, 'stock' => 70],
                ['name' => 'PS5 Console', 'cat' => 'gaming', 'brand' => 'Sony', 'price' => 65000, 'old' => 72000, 'stock' => 20],
                ['name' => 'Logitech Mechanical Keyboard', 'cat' => 'accessories', 'brand' => 'Lenovo', 'price' => 8999, 'old' => 11000, 'stock' => 200],
            ],
            'blogs' => [
                ['title' => 'Best Smartphones of 2026', 'short_desc' => 'Our top picks for smartphones this year.', 'views' => 2100],
                ['title' => 'Gaming Laptop Buying Guide', 'short_desc' => 'How to choose the perfect gaming laptop.', 'views' => 1800],
            ],
            'shipping_charges' => [
                ['name' => 'Inside Dhaka', 'amount' => 70],
                ['name' => 'Outside Dhaka', 'amount' => 120],
            ],
        ];
    }

    // ============================================================
    // PRESET: Natural Food & Grocery (current Ecommerce3)
    // ============================================================
    private static function presetFoodGrocery(): array
    {
        $storeName = 'Natural Food Shop';
        return [
            'meta' => self::all()['food-grocery'],
            'general_settings' => [
                'name'    => $storeName,
                'white_logo' => 'public/assets/images/CurlBazar.svg',
                'dark_logo'  => 'public/assets/images/CurlBazar.svg',
                'favicon'    => 'public/favicon.ico',
                'copyright'  => '© 2026 ' . $storeName . '. All rights reserved.',
                'show_all_products' => 1,
                'show_category_wise_products' => 1,
                'vendor_enabled' => 0,
                'reseller_enabled' => 0,
            ],
            'categories' => [
                ['name' => 'Organic Fruits', 'slug' => 'organic-fruits', 'parent_id' => 0, 'image' => 'public/demo/cat-fruits.jpg'],
                ['name' => 'Fresh Vegetables', 'slug' => 'fresh-vegetables', 'parent_id' => 0, 'image' => 'public/demo/cat-vegetables.jpg'],
                ['name' => 'Dairy & Eggs', 'slug' => 'dairy-eggs', 'parent_id' => 0, 'image' => 'public/demo/cat-dairy.jpg'],
                ['name' => 'Rice & Grains', 'slug' => 'rice-grains', 'parent_id' => 0, 'image' => 'public/demo/cat-grains.jpg'],
                ['name' => 'Beverages', 'slug' => 'beverages', 'parent_id' => 0, 'image' => 'public/demo/cat-beverages.jpg'],
                ['name' => 'Snacks & Biscuits', 'slug' => 'snacks-biscuits', 'parent_id' => 0, 'image' => 'public/demo/cat-snacks.jpg'],
                ['name' => 'Health & Wellness', 'slug' => 'health-wellness', 'parent_id' => 0, 'image' => 'public/demo/cat-health.jpg'],
                ['name' => 'Home & Cleaning', 'slug' => 'home-cleaning', 'parent_id' => 0, 'image' => 'public/demo/cat-cleaning.jpg'],
            ],
            'subcategories' => [
                ['cat' => 'organic-fruits', 'name' => 'Imported Fruits', 'slug' => 'imported-fruits'],
                ['cat' => 'organic-fruits', 'name' => 'Local Fruits', 'slug' => 'local-fruits'],
                ['cat' => 'fresh-vegetables', 'name' => 'Leafy Greens', 'slug' => 'leafy-greens'],
                ['cat' => 'fresh-vegetables', 'name' => 'Root Vegetables', 'slug' => 'root-vegetables'],
                ['cat' => 'dairy-eggs', 'name' => 'Milk', 'slug' => 'milk'],
                ['cat' => 'dairy-eggs', 'name' => 'Cheese', 'slug' => 'cheese'],
                ['cat' => 'rice-grains', 'name' => 'Premium Rice', 'slug' => 'premium-rice'],
                ['cat' => 'rice-grains', 'name' => 'Flour & Pulses', 'slug' => 'flour-pulses'],
                ['cat' => 'beverages', 'name' => 'Soft Drinks', 'slug' => 'soft-drinks'],
                ['cat' => 'beverages', 'name' => 'Juices', 'slug' => 'juices'],
                ['cat' => 'snacks-biscuits', 'name' => 'Chips', 'slug' => 'chips'],
                ['cat' => 'snacks-biscuits', 'name' => 'Chocolates', 'slug' => 'chocolates'],
                ['cat' => 'health-wellness', 'name' => 'Vitamins', 'slug' => 'vitamins'],
                ['cat' => 'health-wellness', 'name' => 'Herbal Supplements', 'slug' => 'herbal-supplements'],
                ['cat' => 'home-cleaning', 'name' => 'Detergents', 'slug' => 'detergents'],
                ['cat' => 'home-cleaning', 'name' => 'Cleaning Tools', 'slug' => 'cleaning-tools'],
            ],
            'brands' => ['Fresh', 'Pran', 'Ruchi', 'Danish', 'Nestlé', 'Pepsi', 'Coca-Cola', 'Dabur', 'Unilever', 'Tata'],
            'products' => [
                ['name' => 'Organic Apple 1kg', 'cat' => 'organic-fruits', 'brand' => 'Fresh', 'price' => 350, 'old' => 420, 'stock' => 500],
                ['name' => 'Imported Grape 500g', 'cat' => 'organic-fruits', 'brand' => 'Fresh', 'price' => 280, 'old' => 350, 'stock' => 400],
                ['name' => 'Fresh Tomato 1kg', 'cat' => 'fresh-vegetables', 'brand' => 'Fresh', 'price' => 120, 'old' => 150, 'stock' => 800],
                ['name' => 'Green Spinach 500g', 'cat' => 'fresh-vegetables', 'brand' => 'Fresh', 'price' => 60, 'old' => 80, 'stock' => 600],
                ['name' => 'Milk 1L (Full Cream)', 'cat' => 'dairy-eggs', 'brand' => 'Danish', 'price' => 130, 'old' => 150, 'stock' => 1000],
                ['name' => 'Eggs (12 pcs)', 'cat' => 'dairy-eggs', 'brand' => 'Pran', 'price' => 165, 'old' => 190, 'stock' => 2000],
                ['name' => 'Premium Miniket Rice 5kg', 'cat' => 'rice-grains', 'brand' => 'Ruchi', 'price' => 550, 'old' => 650, 'stock' => 500],
                ['name' => 'Coca-Cola 2L', 'cat' => 'beverages', 'brand' => 'Coca-Cola', 'price' => 95, 'old' => 120, 'stock' => 1500],
                ['name' => 'Fresh Orange Juice 1L', 'cat' => 'beverages', 'brand' => 'Pran', 'price' => 180, 'old' => 220, 'stock' => 600],
                ['name' => 'Potato Chips Family Pack', 'cat' => 'snacks-biscuits', 'brand' => 'Pran', 'price' => 250, 'old' => 300, 'stock' => 800],
                ['name' => 'Dabur Honey 500g', 'cat' => 'health-wellness', 'brand' => 'Dabur', 'price' => 650, 'old' => 800, 'stock' => 300],
                ['name' => 'Vitamins C Tablets 100pc', 'cat' => 'health-wellness', 'brand' => 'Tata', 'price' => 450, 'old' => 550, 'stock' => 400],
                ['name' => 'Wheel Detergent Powder 1kg', 'cat' => 'home-cleaning', 'brand' => 'Unilever', 'price' => 180, 'old' => 220, 'stock' => 700],
                ['name' => 'Floor Cleaner 500ml', 'cat' => 'home-cleaning', 'brand' => 'Unilever', 'price' => 150, 'old' => 190, 'stock' => 500],
            ],
            'blogs' => [
                ['title' => 'Benefits of Organic Food', 'short_desc' => 'Why organic food is better for your health.', 'views' => 850],
                ['title' => 'Healthy Eating Guide', 'short_desc' => 'Tips for a balanced diet and healthy lifestyle.', 'views' => 1200],
            ],
            'shipping_charges' => [
                ['name' => 'Inside Dhaka', 'amount' => 50],
                ['name' => 'Outside Dhaka', 'amount' => 90],
            ],
        ];
    }

    // ============================================================
    // PRESET: Clothing Fashion
    // ============================================================
    private static function presetClothingFashion(): array
    {
        $storeName = 'Fashion Hub BD';
        return [
            'meta' => self::all()['clothing-fashion'],
            'general_settings' => [
                'name'    => $storeName,
                'white_logo' => 'public/assets/images/CurlBazar.svg',
                'dark_logo'  => 'public/assets/images/CurlBazar.svg',
                'favicon'    => 'public/favicon.ico',
                'copyright'  => '© 2026 ' . $storeName . '. All rights reserved.',
                'show_all_products' => 1,
                'show_category_wise_products' => 1,
                'vendor_enabled' => 0,
                'reseller_enabled' => 0,
            ],
            'categories' => [
                ['name' => 'Men\'s Fashion', 'slug' => 'mens-fashion', 'parent_id' => 0, 'image' => 'public/demo/cat-mens.jpg'],
                ['name' => 'Women\'s Fashion', 'slug' => 'womens-fashion', 'parent_id' => 0, 'image' => 'public/demo/cat-womens.jpg'],
                ['name' => 'Kid\'s Fashion', 'slug' => 'kids-fashion', 'parent_id' => 0, 'image' => 'public/demo/cat-kids.jpg'],
                ['name' => 'Footwear', 'slug' => 'footwear', 'parent_id' => 0, 'image' => 'public/demo/cat-footwear.jpg'],
                ['name' => 'Bags & Luggage', 'slug' => 'bags-luggage', 'parent_id' => 0, 'image' => 'public/demo/cat-bags.jpg'],
                ['name' => 'Accessories', 'slug' => 'accessories', 'parent_id' => 0, 'image' => 'public/demo/cat-accessories.jpg'],
                ['name' => 'Winter Collection', 'slug' => 'winter-collection', 'parent_id' => 0, 'image' => 'public/demo/cat-winter.jpg'],
                ['name' => 'Traditional Wear', 'slug' => 'traditional-wear', 'parent_id' => 0, 'image' => 'public/demo/cat-traditional.jpg'],
            ],
            'subcategories' => [
                ['cat' => 'mens-fashion', 'name' => 'T-Shirts', 'slug' => 'tshirts'],
                ['cat' => 'mens-fashion', 'name' => 'Shirts', 'slug' => 'shirts'],
                ['cat' => 'mens-fashion', 'name' => 'Jeans', 'slug' => 'jeans'],
                ['cat' => 'womens-fashion', 'name' => 'Dresses', 'slug' => 'dresses'],
                ['cat' => 'womens-fashion', 'name' => 'Tops', 'slug' => 'tops'],
                ['cat' => 'womens-fashion', 'name' => 'Skirts', 'slug' => 'skirts'],
                ['cat' => 'kids-fashion', 'name' => 'Boys', 'slug' => 'boys'],
                ['cat' => 'kids-fashion', 'name' => 'Girls', 'slug' => 'girls'],
                ['cat' => 'footwear', 'name' => 'Sneakers', 'slug' => 'sneakers'],
                ['cat' => 'footwear', 'name' => 'Sandals', 'slug' => 'sandals'],
                ['cat' => 'bags-luggage', 'name' => 'Backpacks', 'slug' => 'backpacks'],
                ['cat' => 'bags-luggage', 'name' => 'Handbags', 'slug' => 'handbags'],
                ['cat' => 'accessories', 'name' => 'Belts', 'slug' => 'belts'],
                ['cat' => 'accessories', 'name' => 'Sunglasses', 'slug' => 'sunglasses'],
                ['cat' => 'winter-collection', 'name' => 'Jackets', 'slug' => 'jackets'],
                ['cat' => 'traditional-wear', 'name' => 'Panjabi', 'slug' => 'panjabi'],
            ],
            'brands' => ['Zara', 'H&M', 'Nike', 'Adidas', 'Puma', 'Gucci', 'Levi\'s', 'Denim', 'Mango', 'Uniqlo'],
            'products' => [
                ['name' => 'Zara Slim Fit Blazer', 'cat' => 'mens-fashion', 'brand' => 'Zara', 'price' => 8999, 'old' => 12000, 'stock' => 80],
                ['name' => 'Levi\'s 501 Jeans', 'cat' => 'mens-fashion', 'brand' => 'Levi\'s', 'price' => 5499, 'old' => 6500, 'stock' => 200],
                ['name' => 'H&M Cotton T-Shirt', 'cat' => 'mens-fashion', 'brand' => 'H&M', 'price' => 1499, 'old' => 1999, 'stock' => 500],
                ['name' => 'Zara Summer Dress', 'cat' => 'womens-fashion', 'brand' => 'Zara', 'price' => 5999, 'old' => 7500, 'stock' => 120],
                ['name' => 'Mango Printed Top', 'cat' => 'womens-fashion', 'brand' => 'Mango', 'price' => 3499, 'old' => 4500, 'stock' => 150],
                ['name' => 'Nike Air Max 270', 'cat' => 'footwear', 'brand' => 'Nike', 'price' => 15999, 'old' => 18999, 'stock' => 200],
                ['name' => 'Adidas Ultraboost', 'cat' => 'footwear', 'brand' => 'Adidas', 'price' => 18999, 'old' => 22000, 'stock' => 150],
                ['name' => 'Puma Running Shoes', 'cat' => 'footwear', 'brand' => 'Puma', 'price' => 12999, 'old' => 15999, 'stock' => 180],
                ['name' => 'Nike Backpack 40L', 'cat' => 'bags-luggage', 'brand' => 'Nike', 'price' => 4999, 'old' => 5999, 'stock' => 120],
                ['name' => 'Gucci Sunglasses', 'cat' => 'accessories', 'brand' => 'Gucci', 'price' => 12999, 'old' => 15999, 'stock' => 50],
                ['name' => 'Winter Jacket Premium', 'cat' => 'winter-collection', 'brand' => 'H&M', 'price' => 6999, 'old' => 8999, 'stock' => 80],
                ['name' => 'Cotton Panjabi White', 'cat' => 'traditional-wear', 'brand' => 'Denim', 'price' => 2499, 'old' => 3200, 'stock' => 300],
            ],
            'blogs' => [
                ['title' => 'Summer Fashion Trends 2026', 'short_desc' => 'Stay cool and stylish this summer.', 'views' => 1300],
                ['title' => 'How to Style a Blazer', 'short_desc' => 'Blazer styling tips for every occasion.', 'views' => 950],
            ],
            'shipping_charges' => [
                ['name' => 'Inside Dhaka', 'amount' => 70],
                ['name' => 'Outside Dhaka', 'amount' => 130],
            ],
        ];
    }

    // ============================================================
    // PRESET: Beauty Shop
    // ============================================================
    private static function presetBeauty(): array
    {
        $storeName = 'Beauty Shop BD';
        return [
            'meta' => self::all()['beauty'],
            'general_settings' => [
                'name'    => $storeName,
                'white_logo' => 'public/assets/images/CurlBazar.svg',
                'dark_logo'  => 'public/assets/images/CurlBazar.svg',
                'favicon'    => 'public/favicon.ico',
                'copyright'  => '© 2026 ' . $storeName . '. All rights reserved.',
                'show_all_products' => 1,
                'show_category_wise_products' => 1,
                'vendor_enabled' => 0,
                'reseller_enabled' => 0,
            ],
            'categories' => [
                ['name' => 'Skincare', 'slug' => 'skincare', 'parent_id' => 0, 'image' => 'public/demo/cat-skincare.jpg'],
                ['name' => 'Makeup', 'slug' => 'makeup', 'parent_id' => 0, 'image' => 'public/demo/cat-makeup.jpg'],
                ['name' => 'Hair Care', 'slug' => 'hair-care', 'parent_id' => 0, 'image' => 'public/demo/cat-hair.jpg'],
                ['name' => 'Fragrances', 'slug' => 'fragrances', 'parent_id' => 0, 'image' => 'public/demo/cat-fragrance.jpg'],
                ['name' => 'Body Care', 'slug' => 'body-care', 'parent_id' => 0, 'image' => 'public/demo/cat-body.jpg'],
                ['name' => 'Men\'s Grooming', 'slug' => 'mens-grooming', 'parent_id' => 0, 'image' => 'public/demo/cat-grooming.jpg'],
                ['name' => 'Organic & Natural', 'slug' => 'organic-natural', 'parent_id' => 0, 'image' => 'public/demo/cat-organic.jpg'],
                ['name' => 'Beauty Tools', 'slug' => 'beauty-tools', 'parent_id' => 0, 'image' => 'public/demo/cat-tools.jpg'],
            ],
            'subcategories' => [
                ['cat' => 'skincare', 'name' => 'Face Wash', 'slug' => 'face-wash'],
                ['cat' => 'skincare', 'name' => 'Moisturizer', 'slug' => 'moisturizer'],
                ['cat' => 'skincare', 'name' => 'Sunscreen', 'slug' => 'sunscreen'],
                ['cat' => 'makeup', 'name' => 'Foundation', 'slug' => 'foundation'],
                ['cat' => 'makeup', 'name' => 'Lipstick', 'slug' => 'lipstick'],
                ['cat' => 'makeup', 'name' => 'Eye Shadow', 'slug' => 'eye-shadow'],
                ['cat' => 'hair-care', 'name' => 'Shampoo', 'slug' => 'shampoo'],
                ['cat' => 'hair-care', 'name' => 'Hair Oil', 'slug' => 'hair-oil'],
                ['cat' => 'fragrances', 'name' => 'Perfume Women', 'slug' => 'perfume-women'],
                ['cat' => 'fragrances', 'name' => 'Perfume Men', 'slug' => 'perfume-men'],
                ['cat' => 'body-care', 'name' => 'Body Lotion', 'slug' => 'body-lotion'],
                ['cat' => 'body-care', 'name' => 'Hand Wash', 'slug' => 'hand-wash'],
                ['cat' => 'mens-grooming', 'name' => 'Beard Care', 'slug' => 'beard-care'],
                ['cat' => 'mens-grooming', 'name' => 'Shaving', 'slug' => 'shaving'],
                ['cat' => 'organic-natural', 'name' => 'Herbal Products', 'slug' => 'herbal-products'],
                ['cat' => 'beauty-tools', 'name' => 'Makeup Brushes', 'slug' => 'makeup-brushes'],
            ],
            'brands' => ['L\'Oreal', 'Maybelline', 'Dove', 'Nivea', 'Garnier', 'Pond\'s', 'Lakme', 'MAC', 'Burt\'s Bees', 'The Body Shop'],
            'products' => [
                ['name' => 'L\'Oreal Vitamin C Serum', 'cat' => 'skincare', 'brand' => 'L\'Oreal', 'price' => 2499, 'old' => 3200, 'stock' => 400],
                ['name' => 'Nivea Moisturizer 50ml', 'cat' => 'skincare', 'brand' => 'Nivea', 'price' => 899, 'old' => 1200, 'stock' => 600],
                ['name' => 'Lakme Sunscreen SPF 50', 'cat' => 'skincare', 'brand' => 'Lakme', 'price' => 699, 'old' => 899, 'stock' => 500],
                ['name' => 'Maybelline Foundation', 'cat' => 'makeup', 'brand' => 'Maybelline', 'price' => 1899, 'old' => 2400, 'stock' => 350],
                ['name' => 'MAC Lipstick Ruby Woo', 'cat' => 'makeup', 'brand' => 'MAC', 'price' => 2999, 'old' => 3500, 'stock' => 200],
                ['name' => 'Lakme Eye Shadow Palette', 'cat' => 'makeup', 'brand' => 'Lakme', 'price' => 1499, 'old' => 1899, 'stock' => 250],
                ['name' => 'Dove Hair Fall Rescue', 'cat' => 'hair-care', 'brand' => 'Dove', 'price' => 899, 'old' => 1200, 'stock' => 600],
                ['name' => 'Garnier Hair Oil 200ml', 'cat' => 'hair-care', 'brand' => 'Garnier', 'price' => 550, 'old' => 700, 'stock' => 700],
                ['name' => 'The Body Shop Perfume', 'cat' => 'fragrances', 'brand' => 'The Body Shop', 'price' => 4999, 'old' => 5999, 'stock' => 80],
                ['name' => 'Pond\'s Body Lotion 400ml', 'cat' => 'body-care', 'brand' => 'Pond\'s', 'price' => 699, 'old' => 899, 'stock' => 800],
                ['name' => 'Burt\'s Bees Hand Wash', 'cat' => 'body-care', 'brand' => 'Burt\'s Bees', 'price' => 499, 'old' => 650, 'stock' => 500],
                ['name' => 'Nivea Men Beard Kit', 'cat' => 'mens-grooming', 'brand' => 'Nivea', 'price' => 1499, 'old' => 1899, 'stock' => 300],
            ],
            'blogs' => [
                ['title' => 'Skincare Routine for Glowing Skin', 'short_desc' => 'Step-by-step skincare guide for all skin types.', 'views' => 2200],
                ['title' => 'Top Makeup Trends 2026', 'short_desc' => 'Discover the latest makeup trends.', 'views' => 1600],
            ],
            'shipping_charges' => [
                ['name' => 'Inside Dhaka', 'amount' => 60],
                ['name' => 'Outside Dhaka', 'amount' => 110],
            ],
        ];
    }
}
