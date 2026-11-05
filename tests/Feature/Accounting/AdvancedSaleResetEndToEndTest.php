<?php

namespace Tests\Feature\Accounting;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\Accounting\AdvancedAccountingGateway;
use App\Services\Accounting\ManualEntryService;
use App\Services\OrderStatusService;
use App\Services\TransactionReset\SaleAccountingVerifier;
use App\Services\TransactionReset\SaleResetService;
use App\Support\Accounting\ManualPostingResult;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Support\Facades\DB;
use Softmit\DoubleEntry\Enums\SourceType;
use Softmit\DoubleEntry\Models\JournalEntry;
use Softmit\DoubleEntry\Services\PostingFailureLogger;
use Softmit\DoubleEntry\Services\ReversalService;
use Softmit\DoubleEntry\Support\AccountRole;

/**
 * Advanced accounting, end to end: a sale made after go-live is journaled by the real engine when it is confirmed, and
 * the sales reset reverses it through the real reversal. Every journal here was written by the posting code, not a fixture.
 *
 * The sale is unpaid (cash-on-delivery). The reset refuses a sale with cash in the ledger, because reversing cash is not
 * automated, so a paid sale is not part of this test.
 */
class AdvancedSaleResetEndToEndTest extends LiveBooksTestCase
{
    /**
     * A cash-on-delivery sale made after go-live, confirmed (which posts its sale journal) and then delivered. Delivered is
     * the status the dues and the sales reports recognise, so the books and the dues can be checked against each other.
     */
    private function liveCodSale(int $qty = 1): Order
    {
        Cart::instance('pos_shopping')->add([
            'id' => $this->product->id, 'name' => $this->product->name, 'qty' => $qty, 'price' => 200,
            'options' => ['product_id' => $this->product->id, 'purchase_price' => 100],
        ]);
        $this->post('/admin/order/store', [
            'name' => 'Live Customer', 'phone' => '01755555555', 'address' => 'Mirpur, Dhaka', 'area' => $this->shipping->id,
            'payment_type' => 'cod', 'payment_method' => 'Cash', 'paid_amount' => 0,
        ])->assertRedirect();

        $order = Order::query()->latest('id')->firstOrFail();
        $this->post(route('admin.order.confirm'), ['order_id' => $order->id, 'note' => 'Confirmed in the test.'])->assertOk();
        app(OrderStatusService::class)->changeStatusLocked($order->id, OrderStatus::DELIVERED, 'Delivered in the test.');

        return $order->fresh();
    }

    /** The posted original journal of one kind for a sale (not a reversal). */
    private function original(Order $order, string $type): ?JournalEntry
    {
        return JournalEntry::query()
            ->where('source_type', $type)
            ->where('source_id', $order->id)
            ->whereNull('reversal_of_id')
            ->first();
    }

    private function statusOf(JournalEntry $journal): string
    {
        return (string) DB::table('accounting_journal_entries')->where('id', $journal->id)->value('status');
    }

    /** Net debit minus credit per account across the given journals. Zero for every account means they cancel out. */
    private function netByAccount(array $journalIds): array
    {
        return DB::table('accounting_journal_lines')
            ->whereIn('journal_entry_id', $journalIds)
            ->groupBy('account_id')
            ->selectRaw('account_id, SUM(debit - credit) AS net')
            ->pluck('net', 'account_id')
            ->map(fn ($net) => round((float) $net, 2))
            ->all();
    }

    private function stock(): int
    {
        return (int) \App\Models\StockBatch::where('product_id', $this->product->id)->sum('remaining_qty');
    }

    public function test_a_sale_journaled_after_go_live_is_reversed_by_the_reset_with_a_mirrored_journal(): void
    {
        $this->openBooks();
        $inventoryBefore = $this->balanceOf(AccountRole::INVENTORY);
        $stockBefore = $this->stock();

        $order = $this->liveCodSale(1);

        $sale = $this->original($order, SourceType::SALE->value);
        $this->assertNotNull($sale, 'confirming the sale posted its sale journal');
        $this->assertSame('posted', $this->statusOf($sale));
        $this->checkControls('after the live sale');

        $result = app(SaleResetService::class)->reverse($order, User::first(), 'Reset: live sale reversed in the test.', '127.0.0.1');

        $this->assertSame(SaleAccountingVerifier::VERIFIED, $result['accounting']['status']);
        $this->assertSame('reversed', $this->statusOf($sale), 'the original journal is marked reversed, not left posted');

        $reversal = JournalEntry::query()->where('reversal_of_id', $sale->id)->firstOrFail();
        $this->assertSame('posted', $this->statusOf($reversal), 'the reversal is itself posted');
        $this->assertSame((string) $sale->journal_no, $result['accounting']['journals'][0]['original']);
        $this->assertSame((string) $reversal->journal_no, $result['accounting']['journals'][0]['reversal']);

        foreach ($this->netByAccount([$sale->id, $reversal->id]) as $account => $net) {
            $this->assertEqualsWithDelta(0.0, $net, 0.005, "account {$account}: the reversal mirrors the original exactly");
        }

        $this->assertSame([], app(SaleAccountingVerifier::class)->postedSaleJournals($order), 'no sale journal is still posted');
        $this->assertSame($stockBefore, $this->stock(), 'the stock is back in the shop');
        $this->assertEqualsWithDelta($inventoryBefore, $this->balanceOf(AccountRole::INVENTORY), 0.01, 'inventory is back on the books');
        $this->checkControls('after the reset');
    }

    public function test_a_second_reset_of_the_same_sale_is_refused_and_posts_no_second_reversal(): void
    {
        $this->openBooks();
        $order = $this->liveCodSale(1);
        app(SaleResetService::class)->reverse($order, User::first(), 'First reset.', '127.0.0.1');

        $reversalsBefore = JournalEntry::query()->whereNotNull('reversal_of_id')->count();

        $refused = false;
        try {
            app(SaleResetService::class)->reverse($order->fresh(), User::first(), 'Second reset.', '127.0.0.1');
        } catch (\DomainException) {
            $refused = true;
        }

        $this->assertTrue($refused, 'a sale that is already reversed cannot be reversed again');
        $this->assertSame($reversalsBefore, JournalEntry::query()->whereNotNull('reversal_of_id')->count());
        $this->checkControls('after the refused second reset');
    }

    public function test_a_reversal_that_fails_after_it_is_posted_is_rolled_back_with_the_sale(): void
    {
        $this->openBooks();
        $order = $this->liveCodSale(1);
        $sale = $this->original($order, SourceType::SALE->value);
        $stockBefore = $this->stock();

        // The engine posts the reversal, then fails. The reset's transaction must undo the journal with the status.
        // The failure is injected in the manual entry service the gateway delegates to (the gateway itself is final).
        $this->app->instance(ManualEntryService::class, new class(app(PostingFailureLogger::class), app(ReversalService::class)) extends ManualEntryService {
            public function reverseSale(Order $order, string $reason, ?int $actorId = null): ?ManualPostingResult
            {
                parent::reverseSale($order, $reason, $actorId);

                throw new \RuntimeException('Injected failure after the reversal was posted.');
            }
        });
        // The gateway is a singleton that already holds the real service, so it is rebuilt with the injected one.
        $this->app->forgetInstance(AdvancedAccountingGateway::class);

        $failed = false;
        try {
            app(SaleResetService::class)->reverse($order, User::first(), 'Reset that fails.', '127.0.0.1');
        } catch (\RuntimeException) {
            $failed = true;
        }

        $this->assertTrue($failed, 'the injected failure ran');
        $this->assertSame('delivered', $order->fresh()->order_status, 'the status change was rolled back');
        $this->assertSame('posted', $this->statusOf($sale), 'the sale journal is still posted');
        $this->assertSame(0, JournalEntry::query()->whereNotNull('reversal_of_id')->count(), 'the reversal was rolled back too');
        $this->assertSame($stockBefore, $this->stock(), 'the stock was not restored');
        $this->checkControls('after the rolled-back reset');
    }
}
