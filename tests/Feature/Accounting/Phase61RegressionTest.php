<?php

namespace Tests\Feature\Accounting;

use App\Models\Order;
use App\Models\OrderDetails;
use App\Services\CogsCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Softmit\DoubleEntry\Enums\SourceType;
use Tests\TestCase;

class Phase61RegressionTest extends TestCase
{
    use RefreshDatabase;

    private function order(string $invoice = null): Order
    {
        return Order::create([
            'invoice_id' => $invoice ?: 'PH61-'.uniqid(),
            'amount' => 1000,
            'discount' => 0,
            'shipping_charge' => 0,
            'customer_id' => 0,
            'order_status' => 'completed',
            'payment_status' => 'pending',
        ]);
    }

    public function test_persisted_line_cost_is_the_authoritative_cogs_value(): void
    {
        $order = $this->order();
        $line = OrderDetails::create([
            'order_id' => $order->id, 'product_id' => 1, 'product_name' => 'A',
            'purchase_price' => 999, 'sale_price' => 1500, 'qty' => 20,
            'cogs' => 234, 'batch_ids' => [
                ['batch_id' => 10, 'qty' => 3, 'unit_cost' => 10, 'cogs' => 30],
                ['batch_id' => 11, 'qty' => 17, 'unit_cost' => 12, 'cogs' => 204],
            ],
        ]);

        $this->assertSame(234.0, app(CogsCalculator::class)->cogsForOrders(collect([$order])));
        $this->assertSame(234.0, app(CogsCalculator::class)->lineCogs($line));
    }

    public function test_multiple_lines_sum_without_using_current_product_cost(): void
    {
        $order = $this->order();
        foreach ([234, 100, 75.50] as $i => $cost) {
            OrderDetails::create([
                'order_id' => $order->id, 'product_id' => $i + 1, 'product_name' => 'P'.$i,
                'purchase_price' => 999, 'sale_price' => 1500, 'qty' => 1, 'cogs' => $cost,
            ]);
        }

        $this->assertSame(409.5, app(CogsCalculator::class)->periodProfit()['cogs']);
    }

    public function test_sale_cogs_has_a_distinct_closed_source_identity(): void
    {
        $this->assertSame('sale_cogs', SourceType::SALE_COGS->value);
        $this->assertNotSame(SourceType::SALE->value, SourceType::SALE_COGS->value);
    }
}
