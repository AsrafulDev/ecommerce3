<?php

namespace Tests\Feature\Vat;

use App\Models\GeneralSetting;
use App\Models\Product;
use App\Models\ShippingCharge;
use App\Models\User;
use Database\Seeders\DefaultDatabaseSeeder;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * C15: a sale of a product with no VAT rate still goes through, but the owner is warned
 * while VAT is on. With VAT off, there is no warning.
 */
class VatSaleWarningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
        config(['pricing.batch_wise' => false]);
    }

    private function sell(?float $rate): \Illuminate\Testing\TestResponse
    {
        GeneralSetting::query()->update(['vat_enabled' => true, 'vat_prices_include_vat' => false]);
        $product = Product::create([
            'name' => 'Unrated Phone', 'slug' => 'unrated-' . uniqid(), 'category_id' => 1,
            'product_code' => 'UR-' . uniqid(), 'purchase_price' => 100, 'new_price' => 200,
            'stock' => 0, 'status' => 1, 'vat_rate' => $rate,
        ]);
        app(\App\Services\StockManagementService::class)->stockIn($product, ['quantity' => 5, 'unit_cost' => 100]);
        $shipping = ShippingCharge::create(['name' => 'Inside Dhaka', 'amount' => 60, 'status' => '1']);

        Cart::instance('pos_shopping')->add([
            'id' => $product->id, 'name' => $product->name, 'qty' => 1, 'price' => 200,
            'options' => ['product_id' => $product->id, 'purchase_price' => 100],
        ]);

        return $this->post('/admin/order/store', [
            'name' => 'Warn Customer', 'phone' => '01788888888', 'address' => 'Dhaka', 'area' => $shipping->id,
            'payment_type' => 'paid', 'payment_method' => 'Cash', 'paid_amount' => 260, 'save_as_pending' => 0,
        ]);
    }

    public function test_sale_of_an_unrated_product_goes_through_with_a_warning(): void
    {
        $response = $this->sell(null);

        $response->assertRedirect(route('admin.order.create'));
        $this->assertSame(1, \App\Models\Order::count(), 'the sale is not blocked');
        $this->assertStringContainsString('No VAT rate on', (string) session('toastr.0.message') ?: $this->flashedWarning());
    }

    public function test_sale_of_a_rated_product_has_no_warning(): void
    {
        $this->sell(15);

        $this->assertStringNotContainsString('No VAT rate on', $this->flashedWarning());
    }

    private function flashedWarning(): string
    {
        return collect(session()->all())->map(fn ($v) => is_string($v) ? $v : json_encode($v))->implode(' ');
    }
}
