<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding demo data...');

        // ========== BANNER CATEGORIES ==========
        if (DB::table('banner_categories')->count() == 0) {
            $bcNames = [
                1 => 'Sliders',
                5 => 'Slider Bottom Ads',
                6 => 'Footer Top Ads',
                7 => 'Campaign Ads',
                8 => 'Customer Reviews',
                9 => 'Hot Deal Banners',
                10 => 'Homepage Ads',
                11 => 'Homepage Ads 2',
            ];
            foreach ($bcNames as $id => $name) {
                DB::table('banner_categories')->insert([
                    'id' => $id,
                    'name' => $name,
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $this->command->info('- Banner categories created');
        }

        // ========== BANNERS ==========
        if (DB::table('banners')->count() == 0) {
            // Main sliders (category_id = 1)
            $sliders = [
                ['category_id' => 1, 'image' => 'public/demo/slider-1.webp', 'link' => 'https://example.com/shop'],
                ['category_id' => 1, 'image' => 'public/demo/slider-2.webp', 'link' => 'https://example.com/shop'],
                ['category_id' => 1, 'image' => 'public/demo/slider-3.webp', 'link' => 'https://example.com/shop'],
                ['category_id' => 1, 'image' => 'public/demo/slider-4.webp', 'link' => 'https://example.com/shop'],
            ];
            // Slider bottom ads (5)
            $bottomAds = [
                ['category_id' => 5, 'image' => 'public/demo/bottom-ad-1.webp', 'link' => 'https://example.com/shop'],
            ];
            // Footer top ads (6)
            $footerAds = [
                ['category_id' => 6, 'image' => 'public/demo/footer-ad-1.jpg', 'link' => 'https://example.com/shop'],
            ];
            // Campaign ads (7)
            $campaignAds = [
                ['category_id' => 7, 'image' => 'public/demo/campaign-1.jpg', 'link' => 'https://example.com/campaign'],
            ];
            // Customer reviews (8)
            $reviews = [
                ['category_id' => 8, 'image' => 'public/demo/review-1.jpg', 'link' => '#'],
                ['category_id' => 8, 'image' => 'public/demo/review-2.jpg', 'link' => '#'],
                ['category_id' => 8, 'image' => 'public/demo/review-3.jpg', 'link' => '#'],
            ];
            // Homepage ads (10, 11)
            $homeAds = [
                ['category_id' => 10, 'image' => 'public/demo/home-ad-1.jpg', 'link' => 'https://example.com/shop'],
                ['category_id' => 11, 'image' => 'public/demo/home-ad-2.jpg', 'link' => 'https://example.com/shop'],
            ];

            foreach (array_merge($sliders, $bottomAds, $footerAds, $campaignAds, $reviews, $homeAds) as $b) {
                DB::table('banners')->insert([
                    'category_id' => $b['category_id'],
                    'image' => $b['image'],
                    'link' => $b['link'],
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $this->command->info('- Banners created');
        }

        // ========== CATEGORIES ==========
        if (DB::table('categories')->count() == 0) {
            $cats = [
                ['name' => 'Electronics', 'slug' => 'electronics', 'image' => 'public/demo/cat-electronics.jpg'],
                ['name' => 'Fashion', 'slug' => 'fashion', 'image' => 'public/demo/cat-fashion.jpg'],
                ['name' => 'Home & Kitchen', 'slug' => 'home-kitchen', 'image' => 'public/demo/cat-home.jpg'],
                ['name' => 'Beauty & Health', 'slug' => 'beauty-health', 'image' => 'public/demo/cat-beauty.jpg'],
                ['name' => 'Sports & Outdoor', 'slug' => 'sports-outdoor', 'image' => 'public/demo/cat-sports.jpg'],
                ['name' => 'Books & Stationery', 'slug' => 'books-stationery', 'image' => 'public/demo/cat-books.jpg'],
                ['name' => 'Baby & Toys', 'slug' => 'baby-toys', 'image' => 'public/demo/cat-baby.jpg'],
                ['name' => 'Automotive', 'slug' => 'automotive', 'image' => 'public/demo/cat-auto.jpg'],
            ];
            foreach ($cats as $c) {
                DB::table('categories')->insert([
                    'name' => $c['name'],
                    'slug' => $c['slug'],
                    'parent_id' => 0,
                    'image' => $c['image'],
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $this->command->info('- Categories created');
        }

        // ========== SUBCATEGORIES ==========
        if (DB::table('subcategories')->count() == 0) {
            $catIds = DB::table('categories')->pluck('id', 'slug');
            $subs = [
                ['cat' => 'electronics', 'name' => 'Mobile Phones', 'slug' => 'mobile-phones'],
                ['cat' => 'electronics', 'name' => 'Laptops', 'slug' => 'laptops'],
                ['cat' => 'electronics', 'name' => 'Headphones', 'slug' => 'headphones'],
                ['cat' => 'fashion', 'name' => 'Men\'s Clothing', 'slug' => 'mens-clothing'],
                ['cat' => 'fashion', 'name' => 'Women\'s Clothing', 'slug' => 'womens-clothing'],
                ['cat' => 'home-kitchen', 'name' => 'Kitchen Appliances', 'slug' => 'kitchen-appliances'],
                ['cat' => 'home-kitchen', 'name' => 'Home Decor', 'slug' => 'home-decor'],
                ['cat' => 'beauty-health', 'name' => 'Skincare', 'slug' => 'skincare'],
                ['cat' => 'beauty-health', 'name' => 'Makeup', 'slug' => 'makeup'],
            ];
            foreach ($subs as $s) {
                DB::table('subcategories')->insert([
                    'category_id' => $catIds[$s['cat']] ?? 1,
                    'subcategoryName' => $s['name'],
                    'slug' => $s['slug'],
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $this->command->info('- Subcategories created');
        }

        // ========== BRANDS ==========
        if (DB::table('brands')->count() == 0) {
            $brands = ['Samsung', 'Apple', 'Sony', 'LG', 'HP', 'Dell', 'Nike', 'Adidas', 'Zara', 'H&M'];
            foreach ($brands as $b) {
                DB::table('brands')->insert([
                    'name' => $b,
                    'name_bn' => $b,
                    'slug' => Str::slug($b),
                    'image' => 'public/demo/brand-' . Str::slug($b) . '.jpg',
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $this->command->info('- Brands created');
        }

        // ========== PRODUCTS ==========
        if (DB::table('products')->count() == 0) {
            $catIds = DB::table('categories')->pluck('id', 'slug');
            $brandIds = DB::table('brands')->pluck('id', 'name');

            $products = [
                ['name' => 'Samsung Galaxy S24 Ultra', 'cat' => 'electronics', 'brand' => 'Samsung', 'price' => 129999, 'old' => 139999, 'stock' => 50, 'topsale' => 1, 'flashsale' => 1],
                ['name' => 'iPhone 15 Pro Max', 'cat' => 'electronics', 'brand' => 'Apple', 'price' => 149999, 'old' => 159999, 'stock' => 30, 'topsale' => 1, 'flashsale' => 0],
                ['name' => 'Sony WH-1000XM5 Headphones', 'cat' => 'electronics', 'brand' => 'Sony', 'price' => 29999, 'old' => 35000, 'stock' => 100, 'topsale' => 0, 'flashsale' => 1],
                ['name' => 'HP Pavilion Laptop 15', 'cat' => 'electronics', 'brand' => 'HP', 'price' => 65000, 'old' => 72000, 'stock' => 25, 'topsale' => 0, 'flashsale' => 0],
                ['name' => 'Dell XPS 13', 'cat' => 'electronics', 'brand' => 'Dell', 'price' => 115000, 'old' => 125000, 'stock' => 15, 'topsale' => 1, 'flashsale' => 0],
                ['name' => 'Nike Air Max 270', 'cat' => 'fashion', 'brand' => 'Nike', 'price' => 15999, 'old' => 18999, 'stock' => 200, 'topsale' => 1, 'flashsale' => 0],
                ['name' => 'Adidas Ultraboost 23', 'cat' => 'fashion', 'brand' => 'Adidas', 'price' => 18999, 'old' => 22000, 'stock' => 150, 'topsale' => 0, 'flashsale' => 1],
                ['name' => 'Zara Slim Fit Blazer', 'cat' => 'fashion', 'brand' => 'Zara', 'price' => 8999, 'old' => 12000, 'stock' => 80, 'topsale' => 0, 'flashsale' => 0],
                ['name' => 'LG 65" OLED TV', 'cat' => 'electronics', 'brand' => 'LG', 'price' => 185000, 'old' => 210000, 'stock' => 10, 'topsale' => 0, 'flashsale' => 0],
                ['name' => 'Kitchen Blender Pro', 'cat' => 'home-kitchen', 'brand' => 'Samsung', 'price' => 5499, 'old' => 6999, 'stock' => 300, 'topsale' => 0, 'flashsale' => 1],
            ];

            foreach ($products as $i => $p) {
                $pid = DB::table('products')->insertGetId([
                    'name' => $p['name'],
                    'slug' => Str::slug($p['name']) . '-' . ($i + 1),
                    'category_id' => $catIds[$p['cat']] ?? 1,
                    'brand_id' => $brandIds[$p['brand']] ?? 1,
                    'product_code' => 'PRD-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                    'purchase_price' => round($p['price'] * 0.7),
                    'old_price' => $p['old'],
                    'new_price' => $p['price'],
                    'stock' => $p['stock'],
                    'topsale' => $p['topsale'],
                    'flashsale' => $p['flashsale'],
                    'status' => 1,
                    'approval_status' => 'approved',
                    'description' => 'High-quality ' . $p['name'] . ' at the best price in Bangladesh. Original product with warranty.',
                    'meta_description' => 'Buy ' . $p['name'] . ' online at best price in Bangladesh.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Add product image
                DB::table('productimages')->insert([
                    'product_id' => $pid,
                    'image' => 'public/demo/product-' . ($i + 1) . '.jpg',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $this->command->info('- Products created with images');
        }

        // ========== BLOGS ==========
        if (DB::table('blogs')->count() == 0) {
            $blogs = [
                ['title' => 'Top 10 Smartphones of 2026', 'short_desc' => 'Discover the best smartphones available in Bangladesh this year.', 'views' => 1200],
                ['title' => 'Summer Fashion Trends 2026', 'short_desc' => 'Stay ahead of the fashion curve with our summer collection guide.', 'views' => 850],
                ['title' => 'How to Choose the Perfect Laptop', 'short_desc' => 'A comprehensive guide to finding the right laptop for your needs.', 'views' => 2100],
            ];
            foreach ($blogs as $b) {
                DB::table('blogs')->insert([
                    'title' => $b['title'],
                    'slug' => Str::slug($b['title']),
                    'short_description' => $b['short_desc'],
                    'description' => '<p>' . $b['short_desc'] . ' This is a detailed article about ' . $b['title'] . '. Stay tuned for more updates.</p>',
                    'image' => 'public/demo/blog-' . Str::slug($b['title']) . '.jpg',
                    'views' => $b['views'],
                    'status' => 1,
                    'created_at' => now()->subDays(rand(1, 30)),
                    'updated_at' => now(),
                ]);
            }
            $this->command->info('- Blog posts created');
        }

        $this->command->info('✅ Demo data seeding complete!');
        $this->command->info('Note: Placeholder images are set to public/demo/.');
        $this->command->info('Run "php artisan demo:generate-images" to create placeholder images.');
    }
}
