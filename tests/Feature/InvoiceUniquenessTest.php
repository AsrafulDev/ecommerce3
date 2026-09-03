<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Helpers\InvoiceHelper;
use App\Models\Order;

class InvoiceUniquenessTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_invoice_ids_are_unique()
    {
        $ids = [];
        for ($i = 0; $i < 10; $i++) {
            $ids[] = InvoiceHelper::generateInvoiceId();
        }

        $this->assertCount(10, array_unique($ids));

        // Also assert that saving orders with generated ids does not collide
        foreach ($ids as $id) {
            Order::create([
                'invoice_id' => $id,
                'amount' => 10,
                'discount' => 0,
                'shipping_charge' => 0,
                'customer_id' => 1,
                'order_status' => 1,
                'payment_status' => 'pending',
            ]);
        }

        $this->assertEquals(10, Order::count());
    }
}
