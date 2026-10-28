<?php

namespace Tests\Feature\Accounting;

use App\Models\Order;
use App\Models\User;
use App\Models\WarrantySale;

/**
 * The A4 and A5 sales invoices show "Sold By" under the date: the staff member who sold
 * the products, from the sale rows. Orders with no recorded seller show a dash.
 */
class InvoiceSoldByTest extends LiveBooksTestCase
{
    public function test_sold_by_is_the_staff_member_who_made_the_sale(): void
    {
        $this->openBooks();
        $order = Order::firstOrFail();
        $seller = User::first()->name;

        foreach (['a4', 'a5'] as $type) {
            $this->get(route('admin.order.print', ['invoice_id' => $order->invoice_id, 'type' => $type]))
                ->assertOk()
                ->assertSee('Sold By:')
                ->assertSee('Sold By:</strong> ' . $seller, false);
        }
    }

    public function test_sold_by_is_a_dash_when_no_seller_was_recorded(): void
    {
        $this->openBooks();
        $order = Order::firstOrFail();
        WarrantySale::where('order_id', $order->id)->update(['sold_by' => null]);

        $this->get(route('admin.order.print', ['invoice_id' => $order->invoice_id, 'type' => 'a4']))
            ->assertOk()
            ->assertSee('Sold By:</strong> —', false)
            ->assertDontSee('Electronics Shop BD');
    }
}
