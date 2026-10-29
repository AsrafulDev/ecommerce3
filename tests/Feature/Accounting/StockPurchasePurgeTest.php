<?php

namespace Tests\Feature\Accounting;

use App\Models\FinancialTransactionPurgeLog;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\User;
use App\Services\Accounting\AdvancedAccountingGateway;
use App\Services\Accounting\PurchaseProductPurgeService;
use App\Services\StockManagementService;
use Illuminate\Support\Facades\DB;

/**
 * Super-admin purge of purchases and products. A record that anything still depends on is refused;
 * an unused purchase is removed with its journal, its batches and its stock, and the books still balance.
 */
class StockPurchasePurgeTest extends LiveBooksTestCase
{
    private const PASSWORD = 'purge-test-pass';

    protected function setUp(): void
    {
        parent::setUp();
        // Sign in again after setting the password, so the request checks the new hash.
        User::first()->update(['password' => bcrypt(self::PASSWORD)]);
        $this->actingAs(User::first()->fresh(), 'admin');
    }

    private function purgeRoute(string $type, int $id): string
    {
        return route('admin.transaction-control.purge', [$type, $id]);
    }

    private function purgeInput(string $type, int $id, string $password = self::PASSWORD): array
    {
        return [
            'reason' => 'Entered by mistake during testing',
            'password' => $password,
            'confirmation' => 'DELETE ' . strtoupper($type) . '-' . $id,
        ];
    }

    private function newPurchase(Product $product, string $invoice, float $paid = 0): Purchase
    {
        $this->post(route('purchases.store'), [
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now()->toDateString(),
            'invoice_no' => $invoice,
            'items' => [['product_id' => $product->id, 'qty' => 5, 'unit_cost' => 40]],
            'paid_amount' => $paid,
        ])->assertSessionHasNoErrors();

        return Purchase::where('invoice_no', $invoice)->firstOrFail();
    }

    private function unusedProduct(string $code): Product
    {
        $category = \App\Models\Category::create(['name' => 'Spare ' . $code, 'slug' => 'spare-' . $code, 'status' => 1, 'parent_id' => 0]);

        return Product::create([
            'name' => 'Unused ' . $code, 'slug' => 'unused-' . $code, 'category_id' => $category->id,
            'product_code' => $code, 'purchase_price' => 40, 'new_price' => 80, 'stock' => 0, 'status' => 1,
            'approval_status' => 'approved',
        ]);
    }

    public function test_an_unused_purchase_is_purged_with_its_journal_stock_and_batches(): void
    {
        $this->openBooks();
        $other = $this->unusedProduct('PURGE-1');
        $purchase = $this->newPurchase($other, 'PUR-PURGE-1');

        $inspection = app(PurchaseProductPurgeService::class)->inspect('purchase', $purchase->id);
        $this->assertTrue($inspection['eligible'], implode(', ', $inspection['blockers']));
        $this->assertSame(5, (int) $other->fresh()->stock);
        $this->assertTrue(app(AdvancedAccountingGateway::class)->financialJournalExists('purchase', $purchase->id));

        $this->post($this->purgeRoute('purchase', $purchase->id), $this->purgeInput('purchase', $purchase->id))
            ->assertSessionHas('success')
            ->assertSessionHasNoErrors();

        $this->assertNull(Purchase::find($purchase->id));
        $this->assertSame(0, DB::table('stock_batches')->where('product_id', $other->id)->count());
        $this->assertSame(0, (int) $other->fresh()->stock);
        $this->assertFalse(app(AdvancedAccountingGateway::class)->financialJournalExists('purchase', $purchase->id));
        $this->assertTrue(FinancialTransactionPurgeLog::where('transaction_type', 'purchase')->where('source_id', $purchase->id)->exists());

        $this->checkControls('after the purchase purge');
        $this->get(route('purchases.index'))->assertOk();
        $this->get(route('admin.accounting.reports.trial-balance'))->assertOk();
    }

    public function test_a_purchase_whose_stock_was_sold_is_refused(): void
    {
        $this->openBooks();
        $purchase = Purchase::firstOrFail();

        $inspection = app(PurchaseProductPurgeService::class)->inspect('purchase', $purchase->id);
        $this->assertFalse($inspection['eligible']);
        $this->assertContains('BATCH_SOLD_OR_ADJUSTED', $inspection['blockers']);

        $this->post($this->purgeRoute('purchase', $purchase->id), $this->purgeInput('purchase', $purchase->id))
            ->assertSessionHasErrors('purge');
        $this->assertNotNull(Purchase::find($purchase->id));
    }

    public function test_a_purchase_with_a_payment_is_refused(): void
    {
        $this->openBooks();
        $other = $this->unusedProduct('PURGE-PAID');
        $purchase = $this->newPurchase($other, 'PUR-PAID-1', 100);

        $inspection = app(PurchaseProductPurgeService::class)->inspect('purchase', $purchase->id);
        $this->assertContains('SUPPLIER_PAYMENTS_EXIST', $inspection['blockers']);
    }

    public function test_an_unused_product_is_purged(): void
    {
        $product = $this->unusedProduct('PURGE-PROD');
        $id = $product->id;

        $this->post($this->purgeRoute('product', $id), $this->purgeInput('product', $id))
            ->assertSessionHas('success');

        $this->assertNull(Product::find($id));
        $this->assertTrue(FinancialTransactionPurgeLog::where('transaction_type', 'product')->where('source_id', $id)->exists());
        $this->get(route('admin.transaction-control.index'))->assertOk();
        $this->get(route('inhouse.products.index'))->assertOk();
        $this->get(route('admin.reports.stock'))->assertOk();
    }

    public function test_a_product_with_stock_history_is_refused(): void
    {
        $this->openBooks();

        $inspection = app(PurchaseProductPurgeService::class)->inspect('product', $this->product->id);
        $this->assertFalse($inspection['eligible']);
        $this->assertNotEmpty(array_filter($inspection['blockers'], fn ($b) => str_starts_with($b, 'PRODUCT_USED_IN_')));
        $this->assertNotNull(Product::find($this->product->id));
    }

    public function test_a_wrong_password_or_confirmation_purges_nothing(): void
    {
        $this->openBooks();
        $other = $this->unusedProduct('PURGE-WRONG');
        $purchase = $this->newPurchase($other, 'PUR-WRONG-1');

        $this->post($this->purgeRoute('purchase', $purchase->id), $this->purgeInput('purchase', $purchase->id, 'not-the-password'))
            ->assertSessionHasErrors('password');

        $input = $this->purgeInput('purchase', $purchase->id);
        $input['confirmation'] = 'DELETE PURCHASE-999';
        $this->post($this->purgeRoute('purchase', $purchase->id), $input)
            ->assertSessionHasErrors('confirmation');

        $this->assertNotNull(Purchase::find($purchase->id));
    }

    public function test_a_user_without_the_purge_permission_is_refused(): void
    {
        $this->openBooks();
        $staff = User::create([
            'name' => 'Plain Staff', 'email' => 'plain-' . uniqid() . '@example.test',
            'password' => bcrypt('secret'), 'status' => 1,
        ]);

        $this->actingAs($staff, 'admin');
        $this->get(route('admin.transaction-control.index'))->assertForbidden();
    }
}
