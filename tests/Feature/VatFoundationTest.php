<?php

namespace Tests\Feature;

use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * VAT data foundation (Mushak): columns exist, are empty by default, and the
 * models can store them. Nothing computes them yet, so existing behaviour is
 * unchanged: a sale or purchase created today has no VAT values.
 */
class VatFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_vat_columns_exist(): void
    {
        $expected = [
            'general_settings' => ['vat_bin', 'vat_business_name', 'vat_business_address'],
            'products' => ['vat_rate', 'vat_category', 'hs_code'],
            'order_details' => ['vat_rate', 'taxable_value', 'vat_amount'],
            'purchase_items' => ['vat_rate', 'taxable_value', 'vat_amount'],
            'orders' => ['buyer_bin', 'mushak_invoice_no'],
            'suppliers' => ['bin'],
        ];

        foreach ($expected as $table => $columns) {
            foreach ($columns as $column) {
                $this->assertTrue(Schema::hasColumn($table, $column), "{$table}.{$column} must exist");
            }
        }
    }

    public function test_vat_values_are_stored_on_a_supplier_and_a_product(): void
    {
        $supplier = Supplier::create(['name' => 'VAT Supplier', 'bin' => '123456789012']);
        $this->assertSame('123456789012', $supplier->fresh()->bin);

        $product = Product::create([
            'name' => 'VAT Item', 'slug' => 'vat-item-' . uniqid(), 'category_id' => 1,
            'product_code' => 'VAT-' . uniqid(), 'purchase_price' => 100, 'new_price' => 200,
            'stock' => 0, 'status' => 1, 'vat_rate' => 15, 'hs_code' => '8517.13', 'vat_category' => 'standard',
        ]);
        $fresh = $product->fresh();
        $this->assertSame('15.00', (string) $fresh->vat_rate);
        $this->assertSame('8517.13', $fresh->hs_code);
    }

    public function test_new_purchase_and_sale_lines_carry_no_vat_until_calculated(): void
    {
        $line = PurchaseItem::make(['qty' => 1, 'unit_cost' => 100]);
        $this->assertNull($line->vat_rate);
        $this->assertNull($line->vat_amount);

        $detail = OrderDetails::make(['qty' => 1, 'price' => 200]);
        $this->assertNull($detail->taxable_value);
        $this->assertNull($detail->vat_amount);
    }
}
