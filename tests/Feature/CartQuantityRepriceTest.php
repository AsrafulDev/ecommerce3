<?php

namespace Tests\Feature;

use App\Http\Controllers\Frontend\ShoppingController;
use App\Models\BatchWholesalePrice;
use App\Models\Product;
use App\Models\StockBatch;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * ⭐ Cart qty change must reprice from the ACTUAL pricing method (regression):
 *
 * A batch wholesale tier of 2–5 pcs (e.g. − ৳20) is applied when the line is
 * added at qty 2–5. Previously, changing the qty in the cart only repriced under
 * `per_batch` billing — and even then it re-used the stale wholesale amount — so
 * dropping to 1 pc kept the discounted unit price instead of restoring the full
 * active-batch price. Both +/- must now call `repriceCartRow()`.
 */
class CartQuantityRepriceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Batch-wise is the system default; test explicitly for clarity.
        config(['pricing.batch_wise' => true]);
        config(['pricing.cache_website_price' => false]);
        config(['pricing.multi_batch_pricing' => 'active_batch']);

        // The test session store is shared per process — always start empty.
        Cart::instance('shopping')->destroy();
    }

    protected function makeProduct(): Product
    {
        return Product::create([
            'name'           => 'Reprice Product',
            'slug'           => 'reprice-product-'.uniqid(),
            'category_id'    => 1,
            'product_code'   => 'RP-'.uniqid(),
            'purchase_price' => 50,
            'new_price'      => 100,
            'stock'          => 0,
            'status'         => 1,
        ]);
    }

    protected function makeActiveBatch(Product $product): StockBatch
    {
        return StockBatch::create([
            'product_id'            => $product->id,
            'quantity'              => 50,
            'remaining_qty'         => 50,
            'unit_cost'             => 40,
            'selling_price'         => 100.00,
            'pos_enabled'           => true,
            'auto_advance'          => true,
            'is_active_for_website' => true,
        ]);
    }

    /** Add a line exactly the way FrontendController::cartStore does (qty tier). */
    protected function addLine(Product $product, StockBatch $batch, int $qty, float $unit, float $wholesale): void
    {
        Cart::instance('shopping')->add([
            'id'      => $product->id,
            'name'    => $product->name,
            'qty'     => $qty,
            'price'   => $unit,
            'options' => [
                'image'               => 'public/uploads/default.webp',
                'slug'                => $product->slug,
                'base_price'          => 100.00,
                'sale_price'          => 100.00,
                'regular_price'       => 100.00,
                'warranty_tier_id'    => null,
                'warranty_adjustment' => 0,
                'wholesale_discount'  => $wholesale,
                'batch_id'            => $batch->id,
            ],
        ]);
    }

    public function test_decrement_below_wholesale_tier_restores_full_price(): void
    {
        $product = $this->makeProduct();
        $batch   = $this->makeActiveBatch($product);

        // Wholesale tier: qty 2–5 ⇒ − ৳20 (base ৳100 → ৳80)
        BatchWholesalePrice::create([
            'stock_batch_id'   => $batch->id,
            'variant_price_id' => null,
            'min_quantity'     => 2,
            'max_quantity'     => 5,
            'wholesale_price'  => 20.00,
        ]);

        // Add at qty 3 (inside the 2–5 tier) → discounted ৳80/unit
        $this->addLine($product, $batch, 3, 80.00, 20.00);
        $controller = new ShoppingController();

        // qty 3 → 2 : still inside the tier → discount stays
        $controller->cart_decrement(Request::create('/cart/decrement', 'GET', ['id' => Cart::instance('shopping')->content()->first()->rowId]));
        $item = Cart::instance('shopping')->content()->first();
        $this->assertSame(2, (int) $item->qty);
        $this->assertEqualsWithDelta(80.00, (float) $item->price, 0.001, '2 pcs still in 2–5 tier should stay discounted');
        $this->assertEqualsWithDelta(20.00, (float) $item->options->wholesale_discount, 0.001);

        // qty 2 → 1 : below the tier → FULL ৳100 price must be restored
        $controller->cart_decrement(Request::create('/cart/decrement', 'GET', ['id' => $item->rowId]));
        $item = Cart::instance('shopping')->content()->first();
        $this->assertSame(1, (int) $item->qty);
        $this->assertEqualsWithDelta(100.00, (float) $item->price, 0.001, '1 pc below tier must revert to full price');
        $this->assertEqualsWithDelta(0.00, (float) $item->options->wholesale_discount, 0.001, 'wholesale discount must clear below the tier');
    }

    public function test_increment_into_wholesale_tier_applies_discount(): void
    {
        $product = $this->makeProduct();
        $batch   = $this->makeActiveBatch($product);

        BatchWholesalePrice::create([
            'stock_batch_id'   => $batch->id,
            'variant_price_id' => null,
            'min_quantity'     => 2,
            'max_quantity'     => 5,
            'wholesale_price'  => 20.00,
        ]);

        // Add at qty 1 → full ৳100 (no tier)
        $this->addLine($product, $batch, 1, 100.00, 0.00);
        $controller = new ShoppingController();

        // qty 1 → 2 : crosses into the 2–5 tier → − ৳20
        $controller->cart_increment(Request::create('/cart/increment', 'GET', ['id' => Cart::instance('shopping')->content()->first()->rowId]));
        $item = Cart::instance('shopping')->content()->first();
        $this->assertSame(2, (int) $item->qty);
        $this->assertEqualsWithDelta(80.00, (float) $item->price, 0.001, '2 pcs should apply the tier discount');
        $this->assertEqualsWithDelta(20.00, (float) $item->options->wholesale_discount, 0.001);
    }

    public function test_increment_capped_at_available_stock(): void
    {
        $product = $this->makeProduct();
        // Only 1 unit remains in the sellable (active) batch — same as the demo
        // product whose product-page add-to-cart correctly caps at 1.
        $batch = StockBatch::create([
            'product_id'            => $product->id,
            'quantity'              => 1,
            'remaining_qty'         => 1,
            'unit_cost'             => 40,
            'selling_price'         => 100.00,
            'pos_enabled'           => true,
            'auto_advance'          => true,
            'is_active_for_website' => true,
        ]);

        // Cart already holds the only available unit.
        $this->addLine($product, $batch, 1, 100.00, 0.00);
        $controller = new ShoppingController();

        // Attempt + → must NOT go above the available stock (stays at 1).
        $resp = $controller->cart_increment(Request::create('/cart/increment', 'GET', ['id' => Cart::instance('shopping')->content()->first()->rowId]));

        $item = Cart::instance('shopping')->content()->first();
        $this->assertSame(1, (int) $item->qty, 'qty must stay at the available stock limit');
        $this->assertSame('1', $resp->headers->get('X-Cart-Stock-Limit'), 'blocked increment should signal the stock limit');
    }
}
