<?php

namespace Tests\Support;

use App\Models\Customer;
use App\Models\GeneralSetting;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\Shipping;

/**
 * Sales and seller settings for the Mushak 6.3 tests. Lines carry their VAT snapshot explicitly, so the tests check what
 * the challan prints, not what the product rate would have produced.
 */
trait VatChallanFixtures
{
    protected const SELLER_BIN = '123456789012';

    protected const BUYER_BIN = '987654321098';

    protected function seedSeller(array $overrides = []): void
    {
        GeneralSetting::query()->update(array_merge([
            'vat_enabled' => true,
            'vat_prices_include_vat' => false,
            'vat_bin' => self::SELLER_BIN,
            'vat_business_name' => 'Curl Bazar Ltd',
            'vat_business_address' => 'House 1, Road 2, Dhaka',
        ], $overrides));
    }

    protected function challanProduct(string $name, ?string $unit = 'Pcs'): Product
    {
        return Product::create([
            'name' => $name, 'slug' => 'ch-'.uniqid(), 'category_id' => 1, 'product_code' => 'CH-'.uniqid(),
            'purchase_price' => 100, 'new_price' => 200, 'stock' => 0, 'status' => 1,
            'vat_rate' => 15, 'vat_category' => 'standard', 'pro_unit' => $unit,
        ]);
    }

    protected function challanBuyer(): int
    {
        return Customer::create([
            'name' => 'Challan buyer', 'slug' => 'cb-'.uniqid(), 'phone' => '0179'.random_int(1000000, 9999999),
            'password' => bcrypt('password'), 'verify' => 1, 'status' => 'active',
        ])->id;
    }

    /**
     * A sale with the given status and lines. Each line is [product, qty, taxable value, vat amount, vat rate].
     * Overrides can set buyer_bin, shipping details, and the order's discount or shipping charge.
     */
    protected function sale(string $invoice, array $lines, string $status = 'delivered', array $overrides = []): Order
    {
        $buyer = $overrides['customer_id'] ?? $this->challanBuyer();
        $shippingOverrides = $overrides['shipping'] ?? [];
        unset($overrides['shipping'], $overrides['customer_id']);

        $order = Order::create(array_merge([
            'invoice_id' => $invoice, 'amount' => 0, 'discount' => 0, 'shipping_charge' => 0,
            'customer_id' => $buyer, 'order_status' => $status, 'buyer_bin' => self::BUYER_BIN,
            'created_at' => now(),
        ], $overrides));

        foreach ($lines as [$product, $qty, $taxable, $vat, $rate]) {
            OrderDetails::create([
                'order_id' => $order->id, 'product_id' => $product->id, 'sale_price' => round($taxable / $qty, 2),
                'qty' => $qty, 'product_name' => $product->name, 'purchase_price' => 100,
                'taxable_value' => $taxable, 'vat_amount' => $vat, 'vat_rate' => $rate,
            ]);
        }

        Shipping::create(array_merge([
            'order_id' => $order->id, 'customer_id' => $buyer, 'name' => 'Challan buyer',
            'phone' => '01790000000', 'address' => 'Gulshan 2, Dhaka', 'area' => 'Inside Dhaka',
        ], $shippingOverrides));

        return $order->fresh();
    }
}
