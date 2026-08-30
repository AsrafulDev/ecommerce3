<?php

namespace Tests\Feature;

use App\Models\Color;
use App\Models\District;
use App\Models\GeneralSetting;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\ShippingCharge;
use App\Models\Size;
use App\Models\User;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Demo panel reset / clean (Admin\DemoController).
 *
 * NOTE: resetSite/cleanSite use TRUNCATE (MySQL DDL) which commits implicitly,
 * so these tests intentionally verify the end state rather than relying on
 * RefreshDatabase's per-test transaction rollback.
 */
class DemoResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
    }

    protected function seedSomeTransactions(): void
    {
        $product = Product::create([
            'name'           => 'Pre Reset Product',
            'slug'           => 'pre-reset-'.uniqid(),
            'category_id'    => 1,
            'product_code'   => 'PRE-'.uniqid(),
            'purchase_price' => 50,
            'new_price'      => 90,
            'stock'          => 5,
            'status'         => 1,
        ]);
        OrderDetails::create([
            'order_id'       => 1,
            'product_id'     => $product->id,
            'product_name'   => $product->name,
            'purchase_price' => 50,
            'sale_price'     => 90,
            'qty'            => 1,
        ]);
    }

    public function test_reset_requires_admin_password(): void
    {
        $this->seedSomeTransactions();
        $productCount = Product::count();

        $this->post('/admin/demo/reset', ['password' => 'wrong-password'])
            ->assertRedirect(route('demo.index'));

        // Nothing was wiped when the password is wrong.
        $this->assertSame($productCount, Product::count());
    }

    public function test_reset_is_full_hard_reset_and_seeds_defaults(): void
    {
        $this->seedSomeTransactions();

        $this->post('/admin/demo/reset', ['password' => '123456'])
            ->assertRedirect(route('demo.index'));

        // Full hard reset — transactions (orders, warranty-linked data) wiped.
        $this->assertSame(0, Product::count());
        $this->assertSame(0, OrderDetails::count());

        // Defaults re-seeded: admin + ACL.
        $this->assertDatabaseHas('users', ['email' => 'asraful@curlware.com']);
        $this->assertTrue(Role::where('name', 'admin')->where('guard_name', 'admin')->exists());
        $this->assertGreaterThan(0, Permission::count());

        // Base settings: sizes, colors, districts.
        $this->assertGreaterThan(0, Size::count());
        $this->assertGreaterThan(0, Color::count());
        $this->assertGreaterThan(0, District::count());
        $this->assertSame(1, GeneralSetting::count());

        // Shipping: Inside Dhaka 70 / Outside Dhaka 120.
        $this->assertDatabaseHas('shipping_charges', ['name' => 'Inside Dhaka', 'amount' => 70]);
        $this->assertDatabaseHas('shipping_charges', ['name' => 'Outside Dhaka', 'amount' => 120]);
    }

    public function test_clean_keeps_admin_but_wipes_data(): void
    {
        $this->seedSomeTransactions();

        $this->post('/admin/demo/clean', ['password' => '123456'])
            ->assertRedirect(route('demo.index'));

        $this->assertSame(0, Product::count());
        $this->assertSame(0, OrderDetails::count());

        // Admin users/roles are preserved so you can still log in.
        $this->assertDatabaseHas('users', ['email' => 'asraful@curlware.com']);
    }
}
