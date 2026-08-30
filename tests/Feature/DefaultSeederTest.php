<?php

namespace Tests\Feature;

use App\Models\Color;
use App\Models\District;
use App\Models\GeneralSetting;
use App\Models\ShippingCharge;
use App\Models\Size;
use App\Models\Theme;
use App\Models\User;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * `DefaultDatabaseSeeder` (used by `migrate:fresh:default` and the demo
 * reset) must seed ONLY the base system data — no demo products.
 */
class DefaultSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_seeder_creates_base_system_data(): void
    {
        $this->seed(DefaultDatabaseSeeder::class);

        // Admin user + roles + permissions.
        $this->assertDatabaseHas('users', ['email' => 'asraful@curlware.com']);
        $this->assertTrue(Role::where('name', 'admin')->where('guard_name', 'admin')->exists());
        $this->assertTrue(Role::where('name', 'customer')->where('guard_name', 'customer')->exists());
        $this->assertGreaterThan(0, Permission::count());

        // General settings (single record).
        $this->assertSame(1, GeneralSetting::count());

        // Default product sizes & colors.
        $this->assertGreaterThan(0, Size::count());
        $this->assertGreaterThan(0, Color::count());

        // Districts & areas.
        $this->assertGreaterThan(0, District::count());

        // Themes + layouts (frontend base).
        $this->assertGreaterThan(0, Theme::count());

        // Shipping methods: Inside Dhaka 70 / Outside Dhaka 120.
        $this->assertDatabaseHas('shipping_charges', ['name' => 'Inside Dhaka', 'amount' => 70]);
        $this->assertDatabaseHas('shipping_charges', ['name' => 'Outside Dhaka', 'amount' => 120]);

        // NO demo products/categories/brands are created.
        $this->assertSame(0, \App\Models\Product::count());
    }
}
