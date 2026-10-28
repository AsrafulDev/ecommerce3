<?php

namespace Tests\Feature\Accounting;

use App\Models\Order;
use App\Models\Purchase;

/**
 * Delivery and stock-in challans are goods-only documents: they name the products,
 * quantities and parties, and never carry a price.
 */
class ChallanPrintTest extends LiveBooksTestCase
{
    public function test_delivery_challan_lists_goods_without_prices(): void
    {
        $this->openBooks();
        $order = Order::firstOrFail();

        $this->get(route('admin.order.challan', ['invoice_id' => $order->invoice_id]))
            ->assertOk()
            ->assertSee('DELIVERY CHALLAN')
            ->assertSee('Ledger Customer')
            ->assertSee('Ledger Item')
            ->assertSee('Total quantity')
            ->assertDontSee('৳')
            ->assertDontSee('Total Invoice Value');
    }

    public function test_stock_in_challan_lists_goods_without_prices(): void
    {
        $this->openBooks();
        $purchase = Purchase::firstOrFail();

        $this->get(route('purchases.challan', $purchase->id))
            ->assertOk()
            ->assertSee('STOCK-IN CHALLAN')
            ->assertSee('Ledger Supplier')
            ->assertSee('Ledger Item')
            ->assertSee('Total quantity')
            ->assertDontSee('৳')
            ->assertDontSee('Unit Cost');
    }

    public function test_challan_register_lists_both_kinds_and_filters_by_type(): void
    {
        $this->openBooks();
        $order = Order::firstOrFail();
        $ref = $order->invoice_display ?? $order->invoice_id;

        $this->get(route('admin.reports.challans', $this->range()))
            ->assertOk()
            ->assertSee($ref)
            ->assertSee('Ledger Supplier')
            ->assertSee('Sales delivery')
            ->assertSee('Stock-in');

        $this->get(route('admin.reports.challans', $this->range() + ['type' => 'sales']))
            ->assertOk()
            ->assertSee($ref)
            ->assertDontSee('Ledger Supplier');

        $this->get(route('admin.reports.challans', $this->range() + ['type' => 'stock_in']))
            ->assertOk()
            ->assertSee('Ledger Supplier')
            ->assertDontSee($ref);
    }

    public function test_challan_register_leaves_out_cancelled_orders(): void
    {
        $this->openBooks();
        $order = Order::firstOrFail();
        $ref = $order->invoice_display ?? $order->invoice_id;

        Order::query()->update(['order_status' => 'cancelled']);

        $this->get(route('admin.reports.challans', $this->range() + ['type' => 'sales']))
            ->assertOk()
            ->assertDontSee($ref);
    }

    /** A wide window, so the test data falls inside it whatever day the suite runs. */
    private function range(): array
    {
        return ['from' => '2020-01-01', 'to' => now()->toDateString()];
    }
}
