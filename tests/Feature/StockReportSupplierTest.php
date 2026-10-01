<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockBatch;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Stock report (/admin/reports/stock): a Supplier column resolved from the
 * product's newest in-stock batch (stock_batches.supplier_id, written at
 * purchase stock-in) + a supplier filter that scopes the table AND the
 * summary stats, so no figure on the page can contradict the list.
 */
class StockReportSupplierTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);

        $admin = User::create([
            'name'     => 'Owner',
            'email'    => 'stock-report@test.local',
            'password' => bcrypt('secret'),
            'status'   => 1,
        ]);
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);
        $admin->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->actingAs($admin, 'admin');
    }

    protected function makeProduct(string $name): Product
    {
        return Product::create([
            'name'           => $name,
            'slug'           => 'sr-' . uniqid(),
            'category_id'    => 1,
            'product_code'   => 'SR-' . uniqid(),
            'purchase_price' => 100,
            'new_price'      => 200,
            'stock'          => 5,
            'status'         => 1,
        ]);
    }

    protected function makeBatch(Product $product, Supplier $supplier): StockBatch
    {
        return StockBatch::create([
            'product_id'    => $product->id,
            'supplier_id'   => $supplier->id,
            'quantity'      => 5,
            'remaining_qty' => 5,
            'unit_cost'     => 100,
            'total_cost'    => 500,
            'type'          => 'in',
        ]);
    }

    public function test_report_shows_the_batch_supplier_per_product(): void
    {
        $alpha   = Supplier::create(['name' => 'Alpha Suppliers', 'phone' => '01700000001']);
        $product = $this->makeProduct('Supplied Widget');
        $this->makeBatch($product, $alpha);

        $this->get(route('admin.reports.stock'))
            ->assertOk()
            ->assertSee('Alpha Suppliers')
            ->assertViewHas('supplierNames', fn ($names) => ($names[$product->id] ?? null) === 'Alpha Suppliers');
    }

    public function test_supplier_filter_scopes_products_and_stats(): void
    {
        $alpha  = Supplier::create(['name' => 'Alpha One', 'phone' => '01700000011']);
        $beta   = Supplier::create(['name' => 'Beta Two', 'phone' => '01700000012']);
        $aProduct = $this->makeProduct('Alpha Item');
        $bProduct = $this->makeProduct('Beta Item');
        $this->makeBatch($aProduct, $alpha);
        $this->makeBatch($bProduct, $beta);

        $response = $this->get(route('admin.reports.stock', ['supplier_id' => $alpha->id]))
            ->assertOk()
            ->assertSee('Alpha Item')
            ->assertDontSee('Beta Item');

        // Stats follow the filter: only Alpha's 5 units / ৳500 batch value.
        $response->assertViewHas('batchQty', 5.0)
            ->assertViewHas('batchValue', 500.0)
            ->assertViewHas('supplierId', $alpha->id);
    }

    public function test_csv_export_carries_the_supplier_column(): void
    {
        $alpha   = Supplier::create(['name' => 'CSV Supplier', 'phone' => '01700000013']);
        $product = $this->makeProduct('CSV Widget');
        $this->makeBatch($product, $alpha);

        $csv = $this->get(route('admin.reports.stock', ['export' => 'csv']))->streamedContent();

        $this->assertStringContainsString('Supplier', $csv);
        $this->assertStringContainsString('CSV Supplier', $csv);
    }

    public function test_product_without_stock_batches_shows_no_supplier(): void
    {
        $this->makeProduct('Never Stocked Thing');

        $this->get(route('admin.reports.stock'))
            ->assertOk()
            ->assertSee('Never Stocked Thing')
            ->assertViewHas('supplierNames', fn ($names) => ! array_key_exists(
                Product::where('name', 'Never Stocked Thing')->value('id'), $names
            ));
    }
}
