<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

        // ========== SUBCATEGORIES (all categories) ==========
        if (DB::table('subcategories')->count() == 0) {
            $catIds = DB::table('categories')->pluck('id', 'slug');
            $subs = [
                // Electronics
                ['cat' => 'electronics', 'name' => 'Mobile Phones', 'slug' => 'mobile-phones'],
                ['cat' => 'electronics', 'name' => 'Laptops', 'slug' => 'laptops'],
                ['cat' => 'electronics', 'name' => 'Headphones', 'slug' => 'headphones'],
                ['cat' => 'electronics', 'name' => 'Tablets', 'slug' => 'tablets'],
                ['cat' => 'electronics', 'name' => 'Cameras', 'slug' => 'cameras'],
                // Fashion
                ['cat' => 'fashion', 'name' => 'Men\'s Clothing', 'slug' => 'mens-clothing'],
                ['cat' => 'fashion', 'name' => 'Women\'s Clothing', 'slug' => 'womens-clothing'],
                ['cat' => 'fashion', 'name' => 'Kid\'s Fashion', 'slug' => 'kids-fashion'],
                ['cat' => 'fashion', 'name' => 'Accessories', 'slug' => 'accessories'],
                ['cat' => 'fashion', 'name' => 'Bags & Luggage', 'slug' => 'bags-luggage'],
                // Home & Kitchen
                ['cat' => 'home-kitchen', 'name' => 'Kitchen Appliances', 'slug' => 'kitchen-appliances'],
                ['cat' => 'home-kitchen', 'name' => 'Home Decor', 'slug' => 'home-decor'],
                ['cat' => 'home-kitchen', 'name' => 'Furniture', 'slug' => 'furniture'],
                ['cat' => 'home-kitchen', 'name' => 'Cookware', 'slug' => 'cookware'],
                // Beauty & Health
                ['cat' => 'beauty-health', 'name' => 'Skincare', 'slug' => 'skincare'],
                ['cat' => 'beauty-health', 'name' => 'Makeup', 'slug' => 'makeup'],
                ['cat' => 'beauty-health', 'name' => 'Hair Care', 'slug' => 'hair-care'],
                ['cat' => 'beauty-health', 'name' => 'Health Supplements', 'slug' => 'health-supplements'],
                // Sports & Outdoor
                ['cat' => 'sports-outdoor', 'name' => 'Fitness Equipment', 'slug' => 'fitness-equipment'],
                ['cat' => 'sports-outdoor', 'name' => 'Outdoor Gear', 'slug' => 'outdoor-gear'],
                ['cat' => 'sports-outdoor', 'name' => 'Sportswear', 'slug' => 'sportswear'],
                ['cat' => 'sports-outdoor', 'name' => 'Cycling', 'slug' => 'cycling'],
                // Books & Stationery
                ['cat' => 'books-stationery', 'name' => 'Academic Books', 'slug' => 'academic-books'],
                ['cat' => 'books-stationery', 'name' => 'Fiction & Literature', 'slug' => 'fiction-literature'],
                ['cat' => 'books-stationery', 'name' => 'Office Stationery', 'slug' => 'office-stationery'],
                ['cat' => 'books-stationery', 'name' => 'Art Supplies', 'slug' => 'art-supplies'],
                // Baby & Toys
                ['cat' => 'baby-toys', 'name' => 'Baby Gear', 'slug' => 'baby-gear'],
                ['cat' => 'baby-toys', 'name' => 'Toys & Games', 'slug' => 'toys-games'],
                ['cat' => 'baby-toys', 'name' => 'Nursery', 'slug' => 'nursery'],
                ['cat' => 'baby-toys', 'name' => 'Learning & Education', 'slug' => 'learning-education'],
                // Automotive
                ['cat' => 'automotive', 'name' => 'Car Accessories', 'slug' => 'car-accessories'],
                ['cat' => 'automotive', 'name' => 'Car Care', 'slug' => 'car-care'],
                ['cat' => 'automotive', 'name' => 'Motorcycle Gear', 'slug' => 'motorcycle-gear'],
                ['cat' => 'automotive', 'name' => 'Spare Parts', 'slug' => 'spare-parts'],
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
            $this->command->info('- Subcategories created (' . count($subs) . ' total)');
        }

        // ========== BRANDS ==========
        if (DB::table('brands')->count() == 0) {
            $brands = ['Samsung', 'Apple', 'Sony', 'LG', 'HP', 'Dell', 'Nike', 'Adidas', 'Zara', 'H&M',
                       'Canon', 'Nikon', 'Bosch', 'Puma', 'LEGO', 'Fisher-Price', 'Toyota', 'Honda',
                       'L\'Oreal', 'Maybelline', 'Dove', 'Yamaha', 'Garmin'];
            $hasNameBn = Schema::hasColumn('brands', 'name_bn');
            foreach ($brands as $b) {
                $brandRow = [
                    'name' => $b,
                    'slug' => Str::slug($b),
                    'image' => 'public/demo/brands/brand-' . Str::slug($b) . '.jpg',
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                if ($hasNameBn) {
                    $brandRow['name_bn'] = $b;
                }
                DB::table('brands')->insert($brandRow);
            }
            $this->command->info('- Brands created (' . count($brands) . ' total)');
        }

        // ========== PRODUCTS (all categories) ==========
        // ⚠️ Stock is batch-wise now (see CLAUDE.md pitfalls #1/#2): products.stock
        // is a denormalized copy, source of truth is stock_batches.remaining_qty.
        // Each product below is created with stock = 0, then given real opening
        // stock via StockManagementService::stockIn() backed by a demo Purchase,
        // exactly like Admin\PurchaseController::store() does — never by setting
        // products.stock directly.
        if (DB::table('products')->count() == 0) {
            $catIds = DB::table('categories')->pluck('id', 'slug');
            $brandIds = DB::table('brands')->pluck('id', 'name');

            // Demo supplier + one opening-stock purchase invoice back every batch
            // created below, so Suppliers/Purchases/Stock Batches all show real,
            // consistent history instead of orphaned stock.
            $supplierId = DB::table('suppliers')->count() === 0
                ? DB::table('suppliers')->insertGetId([
                    'name'       => 'Demo Supplier',
                    'phone'      => '01700000000',
                    'email'      => 'supplier@demo.com',
                    'company'    => 'Demo Supplier Co.',
                    'is_active'  => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                : DB::table('suppliers')->orderBy('id')->value('id');

            $adminId = DB::table('users')->orderBy('id')->value('id') ?? 1;

            $products = [
                // Electronics (5)
                ['name' => 'Samsung Galaxy S24 Ultra', 'cat' => 'electronics', 'brand' => 'Samsung', 'price' => 129999, 'old' => 139999, 'stock' => 50, 'topsale' => 1, 'flashsale' => 1],
                ['name' => 'iPhone 15 Pro Max', 'cat' => 'electronics', 'brand' => 'Apple', 'price' => 149999, 'old' => 159999, 'stock' => 30, 'topsale' => 1, 'flashsale' => 0],
                ['name' => 'Sony WH-1000XM5 Headphones', 'cat' => 'electronics', 'brand' => 'Sony', 'price' => 29999, 'old' => 35000, 'stock' => 100, 'topsale' => 0, 'flashsale' => 1],
                ['name' => 'HP Pavilion Laptop 15', 'cat' => 'electronics', 'brand' => 'HP', 'price' => 65000, 'old' => 72000, 'stock' => 25, 'topsale' => 0, 'flashsale' => 0],
                ['name' => 'Dell XPS 13', 'cat' => 'electronics', 'brand' => 'Dell', 'price' => 115000, 'old' => 125000, 'stock' => 15, 'topsale' => 1, 'flashsale' => 0],
                ['name' => 'Apple iPad Air M2', 'cat' => 'electronics', 'brand' => 'Apple', 'price' => 74999, 'old' => 79999, 'stock' => 40, 'topsale' => 0, 'flashsale' => 1],
                ['name' => 'Canon EOS R50 Camera', 'cat' => 'electronics', 'brand' => 'Canon', 'price' => 85000, 'old' => 92000, 'stock' => 20, 'topsale' => 0, 'flashsale' => 0],
                ['name' => 'LG 65" OLED TV', 'cat' => 'electronics', 'brand' => 'LG', 'price' => 185000, 'old' => 210000, 'stock' => 10, 'topsale' => 0, 'flashsale' => 0],
                // Fashion (6)
                ['name' => 'Nike Air Max 270', 'cat' => 'fashion', 'brand' => 'Nike', 'price' => 15999, 'old' => 18999, 'stock' => 200, 'topsale' => 1, 'flashsale' => 0],
                ['name' => 'Adidas Ultraboost 23', 'cat' => 'fashion', 'brand' => 'Adidas', 'price' => 18999, 'old' => 22000, 'stock' => 150, 'topsale' => 0, 'flashsale' => 1],
                ['name' => 'Zara Slim Fit Blazer', 'cat' => 'fashion', 'brand' => 'Zara', 'price' => 8999, 'old' => 12000, 'stock' => 80, 'topsale' => 0, 'flashsale' => 0],
                ['name' => 'Puma Running Shoes', 'cat' => 'fashion', 'brand' => 'Puma', 'price' => 12999, 'old' => 15999, 'stock' => 180, 'topsale' => 0, 'flashsale' => 1],
                ['name' => 'H&M Cotton T-Shirt', 'cat' => 'fashion', 'brand' => 'H&M', 'price' => 1499, 'old' => 1999, 'stock' => 500, 'topsale' => 0, 'flashsale' => 0],
                ['name' => 'Nike Backpack 40L', 'cat' => 'fashion', 'brand' => 'Nike', 'price' => 4999, 'old' => 5999, 'stock' => 120, 'topsale' => 0, 'flashsale' => 0],
                // Home & Kitchen (4)
                ['name' => 'Kitchen Blender Pro', 'cat' => 'home-kitchen', 'brand' => 'Bosch', 'price' => 5499, 'old' => 6999, 'stock' => 300, 'topsale' => 0, 'flashsale' => 1],
                ['name' => 'Samsung Microwave Oven', 'cat' => 'home-kitchen', 'brand' => 'Samsung', 'price' => 15999, 'old' => 18999, 'stock' => 60, 'topsale' => 0, 'flashsale' => 0],
                ['name' => 'Modern Sofa Set 3-Seater', 'cat' => 'home-kitchen', 'brand' => 'H&M', 'price' => 45000, 'old' => 55000, 'stock' => 15, 'topsale' => 1, 'flashsale' => 0],
                ['name' => 'Non-Stick Cookware Set', 'cat' => 'home-kitchen', 'brand' => 'Bosch', 'price' => 3999, 'old' => 5500, 'stock' => 250, 'topsale' => 0, 'flashsale' => 0],
                // Beauty & Health (4)
                ['name' => 'L\'Oreal Vitamin C Serum', 'cat' => 'beauty-health', 'brand' => 'L\'Oreal', 'price' => 2499, 'old' => 3200, 'stock' => 400, 'topsale' => 1, 'flashsale' => 1],
                ['name' => 'Maybelline Foundation', 'cat' => 'beauty-health', 'brand' => 'Maybelline', 'price' => 1899, 'old' => 2400, 'stock' => 350, 'topsale' => 0, 'flashsale' => 0],
                ['name' => 'Dove Hair Fall Rescue', 'cat' => 'beauty-health', 'brand' => 'Dove', 'price' => 899, 'old' => 1200, 'stock' => 600, 'topsale' => 0, 'flashsale' => 0],
                ['name' => 'Omega-3 Fish Oil Capsules', 'cat' => 'beauty-health', 'brand' => 'Sony', 'price' => 1499, 'old' => 1800, 'stock' => 200, 'topsale' => 0, 'flashsale' => 0],
                // Sports & Outdoor (4)
                ['name' => 'Home Gym Set 20kg', 'cat' => 'sports-outdoor', 'brand' => 'Nike', 'price' => 8999, 'old' => 11000, 'stock' => 40, 'topsale' => 1, 'flashsale' => 0],
                ['name' => 'Camping Tent 4-Person', 'cat' => 'sports-outdoor', 'brand' => 'Adidas', 'price' => 12999, 'old' => 15999, 'stock' => 30, 'topsale' => 0, 'flashsale' => 1],
                ['name' => 'Yoga Mat Premium', 'cat' => 'sports-outdoor', 'brand' => 'Puma', 'price' => 2499, 'old' => 3200, 'stock' => 500, 'topsale' => 0, 'flashsale' => 0],
                ['name' => 'Mountain Bike 26"', 'cat' => 'sports-outdoor', 'brand' => 'Yamaha', 'price' => 35000, 'old' => 42000, 'stock' => 20, 'topsale' => 0, 'flashsale' => 0],
                // Books & Stationery (4)
                ['name' => 'Physics Textbook - HSC', 'cat' => 'books-stationery', 'brand' => 'HP', 'price' => 850, 'old' => 1200, 'stock' => 1000, 'topsale' => 0, 'flashsale' => 0],
                ['name' => 'The Great Gatsby', 'cat' => 'books-stationery', 'brand' => 'H&M', 'price' => 650, 'old' => 899, 'stock' => 800, 'topsale' => 0, 'flashsale' => 0],
                ['name' => 'Executive Desk Organizer', 'cat' => 'books-stationery', 'brand' => 'Dell', 'price' => 1299, 'old' => 1699, 'stock' => 150, 'topsale' => 0, 'flashsale' => 0],
                ['name' => 'Watercolor Paint Set 24', 'cat' => 'books-stationery', 'brand' => 'Canon', 'price' => 1499, 'old' => 1899, 'stock' => 200, 'topsale' => 0, 'flashsale' => 0],
                // Baby & Toys (4)
                ['name' => 'Baby Stroller X100', 'cat' => 'baby-toys', 'brand' => 'Fisher-Price', 'price' => 15999, 'old' => 18999, 'stock' => 35, 'topsale' => 1, 'flashsale' => 0],
                ['name' => 'LEGO City Police Set', 'cat' => 'baby-toys', 'brand' => 'LEGO', 'price' => 4999, 'old' => 5999, 'stock' => 90, 'topsale' => 0, 'flashsale' => 1],
                ['name' => 'Baby Cot with Mattress', 'cat' => 'baby-toys', 'brand' => 'Fisher-Price', 'price' => 22000, 'old' => 26000, 'stock' => 20, 'topsale' => 0, 'flashsale' => 0],
                ['name' => 'Educational Puzzle Set', 'cat' => 'baby-toys', 'brand' => 'LEGO', 'price' => 2499, 'old' => 3200, 'stock' => 300, 'topsale' => 0, 'flashsale' => 0],
                // Automotive (4)
                ['name' => 'Car Dash Camera HD', 'cat' => 'automotive', 'brand' => 'Sony', 'price' => 4999, 'old' => 6500, 'stock' => 100, 'topsale' => 0, 'flashsale' => 1],
                ['name' => 'Car Wax Polish Kit', 'cat' => 'automotive', 'brand' => 'Bosch', 'price' => 1999, 'old' => 2800, 'stock' => 200, 'topsale' => 0, 'flashsale' => 0],
                ['name' => 'Motorcycle Helmet Full Face', 'cat' => 'automotive', 'brand' => 'Yamaha', 'price' => 8999, 'old' => 11000, 'stock' => 60, 'topsale' => 1, 'flashsale' => 0],
                ['name' => 'Car Floor Mats Set', 'cat' => 'automotive', 'brand' => 'Toyota', 'price' => 3499, 'old' => 4500, 'stock' => 150, 'topsale' => 0, 'flashsale' => 0],
            ];

            // One opening-stock purchase invoice bundles every batch created below
            // (mirrors Admin\PurchaseController::store()'s purchase + purchase_items
            // + stock_batches trio, so the seeded data matches the real flow).
            $totalQty = array_sum(array_column($products, 'stock'));
            $subtotal = 0;
            foreach ($products as $p) {
                $subtotal += round($p['price'] * 0.7) * $p['stock'];
            }
            $purchaseId = DB::table('purchases')->insertGetId([
                'supplier_id'   => $supplierId,
                'invoice_no'    => 'INV-DEMO-OPENING',
                'purchase_date' => now()->toDateString(),
                'total_qty'     => $totalQty,
                'subtotal'      => $subtotal,
                'grand_total'   => $subtotal,
                'paid_amount'   => $subtotal,
                'due_amount'    => 0,
                'note'          => 'Opening stock (demo data)',
                'status'        => 1,
                'created_by'    => $adminId,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            $stockService = app(\App\Services\StockManagementService::class);
            $pricingService = app(\App\Services\PricingService::class);

            foreach ($products as $i => $p) {
                $unitCost = round($p['price'] * 0.7);

                $pid = DB::table('products')->insertGetId([
                    'name' => $p['name'],
                    'slug' => Str::slug($p['name']) . '-' . ($i + 1),
                    'category_id' => $catIds[$p['cat']] ?? 1,
                    'brand_id' => $brandIds[$p['brand']] ?? 1,
                    'product_code' => 'PRD-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                    'purchase_price' => $unitCost,
                    'old_price' => $p['old'],
                    'new_price' => $p['price'],
                    'stock' => 0, // batch-wise: never set directly — stockIn() below derives it
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

                DB::table('purchase_items')->insert([
                    'purchase_id' => $purchaseId,
                    'product_id'  => $pid,
                    'qty'         => $p['stock'],
                    'unit_cost'   => $unitCost,
                    'line_total'  => $unitCost * $p['stock'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                // Opening stock batch — creates the stock_batches row and increments
                // products.stock via StockManagementService (source of truth).
                $product = \App\Models\Product::find($pid);
                $batch = $stockService->stockIn($product, [
                    'quantity'       => $p['stock'],
                    'unit_cost'      => $unitCost,
                    'selling_price'  => $p['price'],
                    'mrp'            => $p['old'],
                    'supplier_id'    => $supplierId,
                    'purchase_id'    => $purchaseId,
                    'reference_type' => 'purchase',
                    'reference_id'   => $purchaseId,
                ]);

                // Make it the live website/POS batch (first batch for the product).
                $pricingService->setActiveWebsiteBatch($product, $batch->id);
            }
            $this->command->info('- Products created with images + opening stock batches');
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

        // ========== SHIPPING CHARGES ==========
        if (DB::table('shipping_charges')->count() == 0) {
            DB::table('shipping_charges')->insert([
                [
                    'name'       => 'Inside Dhaka',
                    'amount'     => 70,
                    'status'     => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name'       => 'Outside Dhaka',
                    'amount'     => 120,
                    'status'     => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
            $this->command->info('- Shipping charges seeded: Inside Dhaka 70TK, Outside Dhaka 120TK');
        }

        // ========== EXTRA SUBCATEGORIES (from SubcategorySeeder) ==========
        if (DB::table('subcategories')->count() == 0) {
            $slugMap = [
                'home-gadgets', 'health-beauty', 'hot-offer', 'kitchen-gadgets',
                'security', 'all-kinds-of-rack', 'footwear', 'cream',
            ];
            $catIds = DB::table('categories')->whereIn('slug', $slugMap)->pluck('id', 'slug');

            $allSubs = [];
            foreach ($slugMap as $slug) {
                if (isset($catIds[$slug])) {
                    for ($i = 1; $i <= 7; $i++) {
                        $allSubs[] = [
                            'subcategoryName' => 'Subcategory ' . $i,
                            'slug'            => 'subcategory-' . $i,
                            'category_id'     => $catIds[$slug],
                            'status'          => 1,
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ];
                    }
                }
            }
            if (!empty($allSubs)) {
                DB::table('subcategories')->insert($allSubs);
                $this->command->info('- Extra subcategories created for ' . count($catIds) . ' categories');
            }
        }

        $this->command->info('✅ Demo data seeding complete!');
        $this->command->info('Note: Placeholder images are set to public/demo/.');
        $this->command->info('Run "php artisan demo:generate-images" to create placeholder images.');
    }
}
