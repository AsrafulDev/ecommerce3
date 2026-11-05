<?php

namespace Tests\Feature\TransactionReset;

use App\Models\Category;
use App\Models\GeneralSetting;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The hidden sales reset: cancel reverses what the sale did and keeps it as cancelled; complete delete
 * removes a cancelled sale only when nothing else refers to it; every action needs the admin password.
 */
class SaleResetTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        config(['pricing.batch_wise' => false]);
        $this->seed(DefaultDatabaseSeeder::class);
        $admin = User::first();
        $admin->update(['password' => bcrypt('password')]);
        $this->actingAs($admin, 'admin');
        GeneralSetting::query()->update(['vat_enabled' => false]);

        $category = Category::create(['name' => 'Reset', 'slug' => 'reset-'.uniqid(), 'status' => 1, 'parent_id' => 0]);
        $this->product = Product::create([
            'name' => 'Reset item', 'slug' => 'reset-item-'.uniqid(), 'category_id' => $category->id,
            'product_code' => 'RS-'.uniqid(), 'purchase_price' => 100, 'new_price' => 200, 'stock' => 0, 'status' => 1,
        ]);

        // A realistic sale: stock comes in at 100, the catalogue cost later changes to 150, and the sale is
        // completed through the normal stock-out path, which records the batch it was taken from.
        app(\App\Services\StockManagementService::class)->stockIn($this->product, ['quantity' => 10, 'unit_cost' => 100]);
        $this->product->update(['purchase_price' => 150]);

        $this->order = Order::create([
            'invoice_id' => 'INV-RESET-1', 'amount' => 400, 'discount' => 0, 'shipping_charge' => 0,
            'customer_id' => 1, 'order_status' => 'pending', 'created_at' => now(),
        ]);
        OrderDetails::create([
            'order_id' => $this->order->id, 'product_id' => $this->product->id, 'sale_price' => 200, 'qty' => 2,
            'product_name' => 'Reset item', 'purchase_price' => 150,
        ]);
        $this->order->update(['order_status' => 'completed']);
        app(\App\Services\OrderStatusService::class)->handleStatusChange($this->order, 'pending', 'completed');
        $this->order = $this->order->fresh();
    }

    private function act(array $data): \Illuminate\Testing\TestResponse
    {
        return $this->post(route('admin.transaction-reset.act'), array_merge([
            'reason' => 'Entered by mistake, reset for the test.',
            'password' => 'password',
        ], $data));
    }

    public function test_cancel_reverses_stock_and_keeps_the_sale_as_cancelled(): void
    {
        $this->act(['action' => 'cancel', 'order_ids' => [$this->order->id]])->assertRedirect();

        $this->assertSame('returned', $this->order->fresh()->order_status);
        $this->assertSame(10, (int) $this->product->fresh()->stock);
        $this->assertDatabaseHas('order_details', ['order_id' => $this->order->id]);
    }

    public function test_a_wrong_password_changes_nothing(): void
    {
        $this->act(['action' => 'cancel', 'order_ids' => [$this->order->id], 'password' => 'wrong'])
            ->assertSessionHasErrors('password');

        $this->assertSame('completed', $this->order->fresh()->order_status);
    }

    public function test_complete_delete_is_refused_for_a_sale_that_is_not_cancelled(): void
    {
        $this->act(['action' => 'delete', 'order_ids' => [$this->order->id], 'confirmation' => 'DELETE'])
            ->assertSessionHasErrors('reset');

        $this->assertNotNull(Order::find($this->order->id));
    }

    public function test_complete_delete_needs_the_typed_word(): void
    {
        $this->act(['action' => 'cancel', 'order_ids' => [$this->order->id]]);

        $this->act(['action' => 'delete', 'order_ids' => [$this->order->id], 'confirmation' => 'yes'])
            ->assertSessionHasErrors('confirmation');

        $this->assertNotNull(Order::find($this->order->id));
    }

    public function test_a_cancelled_sale_with_no_money_behind_it_is_completely_deleted(): void
    {
        // A sale that has not left the shop is cancelled through the normal transition, then removed.
        $this->order->update(['order_status' => 'pending']);
        $this->act(['action' => 'cancel', 'order_ids' => [$this->order->id]]);
        $this->act(['action' => 'delete', 'order_ids' => [$this->order->id], 'confirmation' => 'DELETE'])
            ->assertRedirect();

        $this->assertNull(Order::find($this->order->id));
        $this->assertDatabaseMissing('order_details', ['order_id' => $this->order->id]);
    }

    public function test_complete_delete_is_refused_while_a_payment_is_recorded(): void
    {
        Payment::create([
            'order_id' => $this->order->id, 'customer_id' => 1, 'amount' => 100,
            'payment_method' => 'cod', 'payment_status' => 'pending',
        ]);
        $this->act(['action' => 'cancel', 'order_ids' => [$this->order->id]]);

        $this->act(['action' => 'delete', 'order_ids' => [$this->order->id], 'confirmation' => 'DELETE'])
            ->assertSessionHasErrors('reset');

        $this->assertNotNull(Order::find($this->order->id));
    }

    public function test_the_page_lists_sales_and_shows_the_blockers(): void
    {
        $this->get(route('admin.transaction-reset.index'))
            ->assertOk()
            ->assertSee('INV-RESET-1')
            ->assertSee('Sales reset');
    }
}
