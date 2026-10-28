<?php

namespace Tests\Feature\Accounting;

use App\Models\Order;

/**
 * The POS receipt prints the customer's name, phone and address under the invoice line.
 */
class PosReceiptCustomerTest extends LiveBooksTestCase
{
    public function test_pos_receipt_shows_the_customer_details(): void
    {
        $this->openBooks();
        $order = Order::firstOrFail();

        $this->get(route('admin.order.print', ['invoice_id' => $order->invoice_id, 'type' => 'pos']))
            ->assertOk()
            ->assertSee('Customer: Ledger Customer')
            ->assertSee('Phone: 01744444444')
            ->assertSee('Address: Mirpur, Dhaka');
    }

    public function test_pos_receipt_without_customer_details_prints_no_customer_block(): void
    {
        $this->openBooks();
        $order = Order::firstOrFail();
        \App\Models\Shipping::where('order_id', $order->id)->delete();
        $order->customer()->update(['name' => '', 'phone' => '']);

        $this->get(route('admin.order.print', ['invoice_id' => $order->invoice_id, 'type' => 'pos']))
            ->assertOk()
            ->assertDontSee('Customer:');
    }
}
