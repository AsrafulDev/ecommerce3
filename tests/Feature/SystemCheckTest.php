<?php

namespace Tests\Feature;

use App\Helpers\FundHelper;
use App\Models\FundTransaction;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\User;
use App\Services\Audit\SystemCheck;
use App\Services\Audit\SystemRepair;
use App\Services\StockManagementService;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemCheckTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
        config(['pricing.batch_wise' => false]);
    }

    private function product(): Product
    {
        $category = \App\Models\Category::create(['name' => 'Sys', 'slug' => 'sys-'.uniqid(), 'status' => 1, 'parent_id' => 0]);
        $product = Product::create([
            'name' => 'Sys Item', 'slug' => 'sys-item-'.uniqid(), 'category_id' => $category->id,
            'product_code' => 'SY-'.uniqid(), 'purchase_price' => 100, 'new_price' => 200, 'stock' => 0, 'status' => 1,
        ]);
        app(StockManagementService::class)->stockIn($product, ['quantity' => 5, 'unit_cost' => 100]);

        return $product->fresh();
    }

    public function test_the_check_finds_stock_that_disagrees_with_its_batches(): void
    {
        $product = $this->product();
        Product::whereKey($product->id)->update(['stock' => 9]); // a stale copy

        $drift = collect((new SystemCheck())->run())->firstWhere('key', 'stock_drift');

        $this->assertSame(1, $drift['count']);
        $this->assertTrue($drift['repairable']);
    }

    public function test_the_repair_sets_stock_from_batches_and_changes_no_batch_or_cash(): void
    {
        $product = $this->product();
        Product::whereKey($product->id)->update(['stock' => 9]);
        $batchBefore = (int) StockBatch::where("product_id", $product->id)->sum("remaining_qty");
        $tillBefore = FundHelper::balance();

        app(SystemRepair::class)->run();

        $this->assertSame(5, (int) $product->fresh()->stock);
        $this->assertSame($batchBefore, (int) StockBatch::where('product_id', $product->id)->sum('remaining_qty'));
        $this->assertEqualsWithDelta($tillBefore, FundHelper::balance(), 0.01, 'the repair never moves cash');
        $this->assertSame(0, collect((new SystemCheck())->run())->firstWhere('key', 'stock_drift')['count']);
    }

    public function test_the_repair_refuses_to_run_without_confirmation(): void
    {
        $product = $this->product();
        Product::whereKey($product->id)->update(['stock' => 9]);

        $this->post(route('admin.system.repair'), [])->assertSessionHasErrors('confirm');

        $this->assertSame(9, (int) $product->fresh()->stock, 'nothing is repaired without confirmation');
    }

    public function test_the_check_page_shows_each_check_and_the_repair_button(): void
    {
        $this->get(route('admin.system.check'))
            ->assertOk()
            ->assertSee('Product stock that does not match its batches')
            ->assertSee('Run safe repair')
            ->assertSee('needs decision');
    }
}
