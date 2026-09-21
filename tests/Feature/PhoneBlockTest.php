<?php

namespace Tests\Feature;

use App\Models\Cart as CartModel;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PhoneBlock;
use App\Models\Product;
use App\Models\ShippingCharge;
use App\Models\User;
use App\Services\StockManagementService;
use Database\Seeders\DefaultDatabaseSeeder;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Phone-number block list.
 *
 * Online (storefront + mobile app) must hard-refuse a blocked number; the POS
 * only warns, so staff can still serve the customer.
 */
class PhoneBlockTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;
    private ShippingCharge $shipping;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
        config(['pricing.batch_wise' => false]);

        // The block verdict is cached per number — start every test clean so a
        // rolled-back block from another test can't leak in.
        Cache::flush();

        $this->shipping = ShippingCharge::create([
            'name'   => 'Inside Dhaka',
            'amount' => 60,
            'status' => '1',
        ]);

        $this->product = Product::create([
            'name'           => 'Blocked Phone Product',
            'slug'           => 'blocked-phone-'.uniqid(),
            'category_id'    => 1,
            'product_code'   => 'BP-'.uniqid(),
            'purchase_price' => 100,
            'new_price'      => 200,
            'stock'          => 0,
            'status'         => 1,
            'approval_status' => 'approved',
        ]);
        app(StockManagementService::class)->stockIn($this->product, [
            'quantity'  => 10,
            'unit_cost' => 100,
        ]);
    }

    // ---------------------------------------------------------------- helpers

    private function seedStorefrontCart(): void
    {
        Cart::instance('shopping')->add([
            'id'      => $this->product->id,
            'name'    => $this->product->name,
            'qty'     => 1,
            'price'   => 200,
            'options' => ['product_id' => $this->product->id, 'purchase_price' => 100],
        ]);
    }

    // ------------------------------------------------------------- unit-ish

    public function test_all_common_phone_spellings_normalise_the_same(): void
    {
        $expected = '01712345678';

        foreach (['01712345678', '+8801712345678', '8801712345678', '880 1712-345678', '1712345678'] as $variant) {
            $this->assertSame($expected, normalize_phone($variant), "failed for {$variant}");
        }

        $this->assertSame('', normalize_phone(''));
        $this->assertSame('', normalize_phone(null));
        $this->assertSame('', normalize_phone('not-a-number'));
    }

    // --------------------------------------------------------------- admin CRUD

    public function test_admin_can_block_and_unblock_a_number(): void
    {
        $this->post('/admin/customer/phone-store', [
            'phone'  => '+8801711111111',
            'reason' => 'Repeated fake orders',
        ])->assertRedirect();

        $block = PhoneBlock::first();
        $this->assertNotNull($block);
        $this->assertSame('01711111111', $block->phone_normalized, 'stored in canonical form');

        // Cached verdict is now "blocked".
        $this->assertNotNull(is_phone_blocked('01711111111'));

        // Duplicate (in another spelling) is rejected.
        $this->post('/admin/customer/phone-store', [
            'phone'  => '8801711111111',
            'reason' => 'dup',
        ])->assertRedirect();
        $this->assertSame(1, PhoneBlock::count(), 'duplicate block must not be stored');

        // Unblock clears the cached verdict immediately.
        $this->post('/admin/customer/phone-destroy', ['id' => $block->id])->assertRedirect();
        $this->assertSame(0, PhoneBlock::count());
        $this->assertNull(is_phone_blocked('01711111111'), 'unblock must take effect immediately');
    }

    public function test_admin_page_renders_both_ip_and_phone_sections(): void
    {
        PhoneBlock::create(['phone' => '01777777777', 'reason' => 'Visible row']);

        $res = $this->get('/admin/customer/ip-block');
        $res->assertStatus(200);

        $html = $res->getContent();
        $this->assertStringContainsString('Block Phone Number', $html);
        $this->assertStringContainsString('Blocked Phone List', $html);
        $this->assertStringContainsString('01777777777', $html);
        $this->assertStringContainsString('datatable-phone', $html);
        // The IP section must still be there.
        $this->assertStringContainsString('Block New IP', $html);
    }

    public function test_pos_check_endpoint_reports_the_block(): void
    {
        PhoneBlock::create(['phone' => '01722222222', 'reason' => 'Fake order']);

        $this->getJson('/admin/customer/phone-check?phone=01722222222')
            ->assertOk()
            ->assertJson(['blocked' => true, 'reason' => 'Fake order']);

        $this->getJson('/admin/customer/phone-check?phone=01799999999')
            ->assertOk()
            ->assertJson(['blocked' => false]);
    }

    // ---------------------------------------------------------- enforcement

    public function test_storefront_checkout_refuses_a_blocked_phone(): void
    {
        $this->seedStorefrontCart();
        PhoneBlock::create(['phone' => '01733333333', 'reason' => 'Fake order']);

        $res = $this->post(route('customer.ordersave'), [
            'name'           => 'Blocked Buyer',
            'phone'          => '01733333333',
            'address'        => 'Mirpur, Dhaka',
            'area'           => $this->shipping->id,
            // 'bkash' avoids the COD-only Facebook CAPI shutdown hook
            // (see OrderSaveTransactionTest) — the block fires before either.
            'payment_method' => 'bkash',
        ]);

        // Must actually reach the controller (guards against a vacuous 404 pass)
        // and refuse the order.
        $this->assertNotSame(404, $res->getStatusCode(), 'route must exist');
        $this->assertSame(302, $res->getStatusCode(), 'checkout should bounce back');
        $this->assertSame(0, Order::count(), 'blocked phone must not be able to order');
    }

    public function test_storefront_checkout_allows_a_clean_phone(): void
    {
        $this->seedStorefrontCart();

        $res = $this->post(route('customer.ordersave'), [
            'name'           => 'Honest Buyer',
            'phone'          => '01744444444',
            'address'        => 'Mirpur, Dhaka',
            'area'           => $this->shipping->id,
            'payment_method' => 'bkash',
        ]);

        $this->assertNotSame(404, $res->getStatusCode(), 'route must exist');
        $this->assertStringContainsString(
            'bkash/checkout-url/create?order_id=',
            (string) $res->headers->get('Location'),
            'a clean phone must be able to complete checkout'
        );
        $this->assertSame(1, Order::count(), 'a clean phone must still be able to order');
    }

    public function test_mobile_api_refuses_a_blocked_phone(): void
    {
        PhoneBlock::create(['phone' => '01755555555', 'reason' => 'Fake order']);

        $customer = Customer::create([
            'name'     => 'Mobile Blocked',
            'slug'     => 'mobile-blocked-'.uniqid(),
            'phone'    => '01755555555',
            'password' => bcrypt('secret'),
            'verify'   => 1,
            'status'   => 'active',
        ]);
        Sanctum::actingAs($customer);

        CartModel::create([
            'customer_id' => $customer->id,
            'product_id'  => $this->product->id,
            'quantity'    => 1,
            'price'       => 200,
        ]);

        $this->postJson('/api/v1/mobile/orders', [
            'name'           => 'Mobile Blocked',
            'phone'          => '01755555555',
            'address'        => 'Mirpur, Dhaka',
            'area'           => $this->shipping->id,
            'payment_method' => 'cod',
        ])->assertStatus(403)
          ->assertJson(['status' => 'error']);

        $this->assertSame(0, Order::count());
    }

    public function test_pos_can_still_sell_to_a_blocked_number_staff_override(): void
    {
        PhoneBlock::create(['phone' => '01766666666', 'reason' => 'Fake order']);

        Cart::instance('pos_shopping')->add([
            'id'      => $this->product->id,
            'name'    => $this->product->name,
            'qty'     => 1,
            'price'   => 200,
            'options' => ['product_id' => $this->product->id, 'purchase_price' => 100],
        ]);

        $this->post('/admin/order/store', [
            'name'           => 'POS Override',
            'phone'          => '01766666666',
            'address'        => 'Mirpur, Dhaka',
            'area'           => $this->shipping->id,
            'payment_type'   => 'paid',
            'payment_method' => 'Cash',
            'paid_amount'    => 260,
        ])->assertRedirect(route('admin.order.create'));

        $this->assertSame(1, Order::count(), 'POS must still be able to complete the sale');
        $this->assertSame('completed', Order::first()->order_status);
    }
}
