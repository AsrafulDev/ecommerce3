<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductWarrantyTier;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\SupplierWarranty;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WarrantyDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding warranty demo data...');

        // Get or create demo data
        $productA = Product::first() ?? Product::factory()->create(['name' => 'Product A', 'purchase_price' => 400, 'selling_price' => 500]);
        $productB = Product::skip(1)->first() ?? Product::factory()->create(['name' => 'Product B', 'purchase_price' => 300, 'selling_price' => 400]);
        $productC = Product::skip(2)->first() ?? Product::factory()->create(['name' => 'Product C', 'purchase_price' => 350, 'selling_price' => 400]);
        $supplier = Supplier::first() ?? Supplier::factory()->create(['name' => 'Demo Supplier']);

        // Ensure we have purchase + purchase_items for FK integrity
        $purchase = Purchase::first() ?? Purchase::create([
            'supplier_id'   => $supplier->id,
            'invoice_no'    => 'INV-DEMO-' . now()->format('Ymd'),
            'purchase_date' => now(),
            'total_qty'     => 30,
            'subtotal'      => 10000,
            'grand_total'   => 10000,
            'paid_amount'   => 10000,
            'due_amount'    => 0,
            'status'        => 'completed',
        ]);

        $items = [];
        foreach ([$productA, $productB, $productC] as $i => $product) {
            $item = PurchaseItem::where('purchase_id', $purchase->id)
                ->where('product_id', $product->id)
                ->first();

            if (!$item) {
                $item = PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id'  => $product->id,
                    'qty'         => 10,
                    'unit_cost'   => $product->purchase_price,
                    'line_total'  => $product->purchase_price * 10,
                ]);
            }
            $items[$product->id] = $item;
        }

        // ── Supplier Warranties ──────────────────
        $swA = SupplierWarranty::where('product_id', $productA->id)->first();
        if (!$swA) {
            SupplierWarranty::create([
                'purchase_item_id'   => $items[$productA->id]->id,
                'product_id'         => $productA->id,
                'supplier_id'        => $supplier->id,
                'warranty_days'      => 180,
                'warranty_start_date' => now(),
                'warranty_end_date'  => now()->addDays(180),
                'is_transferable'    => true,
            ]);
        }

        $swB = SupplierWarranty::where('product_id', $productB->id)->first();
        if (!$swB) {
            SupplierWarranty::create([
                'purchase_item_id'   => $items[$productB->id]->id,
                'product_id'         => $productB->id,
                'supplier_id'        => $supplier->id,
                'warranty_days'      => 60,
                'warranty_start_date' => now(),
                'warranty_end_date'  => now()->addDays(60),
                'is_transferable'    => true,
            ]);
        }

        // ── Product Warranty Tiers ──────────────

        // Product A
        ProductWarrantyTier::updateOrCreate(
            ['product_id' => $productA->id, 'warranty_type' => 'supplier_warranty'],
            ['tier_name' => 'With Warranty (180 Days)', 'warranty_days' => 180, 'price' => 500, 'sort_order' => 0, 'badge' => '🟢 Recommended', 'is_active' => true]
        );
        ProductWarrantyTier::updateOrCreate(
            ['product_id' => $productA->id, 'warranty_type' => 'none'],
            ['tier_name' => 'No Warranty', 'warranty_days' => 0, 'price' => 450, 'sort_order' => 1, 'is_active' => true]
        );
        ProductWarrantyTier::updateOrCreate(
            ['product_id' => $productA->id, 'warranty_type' => 'extended_warranty'],
            ['tier_name' => 'Extra Warranty (360 Days)', 'warranty_days' => 360, 'price' => 550, 'sort_order' => 2, 'badge' => '🔵 Premium', 'is_active' => true]
        );

        // Product B
        ProductWarrantyTier::updateOrCreate(
            ['product_id' => $productB->id, 'warranty_type' => 'supplier_warranty'],
            ['tier_name' => 'With Warranty (60 Days)', 'warranty_days' => 60, 'price' => 400, 'sort_order' => 0, 'is_active' => true]
        );
        ProductWarrantyTier::updateOrCreate(
            ['product_id' => $productB->id, 'warranty_type' => 'none'],
            ['tier_name' => 'No Warranty', 'warranty_days' => 0, 'price' => 350, 'sort_order' => 1, 'is_active' => true]
        );

        // Product C (no supplier warranty)
        ProductWarrantyTier::updateOrCreate(
            ['product_id' => $productC->id, 'warranty_type' => 'none'],
            ['tier_name' => 'No Warranty', 'warranty_days' => 0, 'price' => 400, 'sort_order' => 0, 'is_active' => true]
        );
        ProductWarrantyTier::updateOrCreate(
            ['product_id' => $productC->id, 'warranty_type' => 'extended_warranty'],
            ['tier_name' => 'Extra Warranty (90 Days)', 'warranty_days' => 90, 'price' => 450, 'sort_order' => 1, 'is_active' => true]
        );

        $this->command->info('Warranty demo data seeded!');
    }
}

