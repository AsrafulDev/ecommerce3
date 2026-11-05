<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Shipping;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Vat\VatCalculator;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Demo purchases and sales for one month, so the Mushak books can be seen with real-looking lines.
 *
 * Every record is named DEMO and its invoice numbers start with DEMO-, so it is easy to tell apart from
 * real data. The command does not change stock, and it refuses to run in production unless --force is given.
 * VAT must already be switched on in the VAT settings: the command does not change settings.
 */
class VatDemoData extends Command
{
    protected $signature = 'vat:demo {--month= : The month to fill, YYYY-MM (default: this month)} {--force : Allow running in production}';

    protected $description = 'Create DEMO purchases and sales for a month so the Mushak books show sample lines';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Refusing to create demo data in production. Use --force only on a database where demo data is wanted.');

            return self::FAILURE;
        }

        if (! app(VatCalculator::class)->enabled()) {
            $this->error('VAT is not switched on. Turn it on in the VAT settings first; this command does not change settings.');

            return self::FAILURE;
        }

        $month = $this->option('month')
            ? Carbon::createFromFormat('Y-m', (string) $this->option('month'))->startOfMonth()
            : now()->startOfMonth();
        $code = $month->format('Ym');

        if (Purchase::where('invoice_no', 'DEMO-PUR-'.$code)->exists()) {
            $this->info("Demo data for {$month->format('Y-m')} already exists; nothing created.");

            return self::SUCCESS;
        }

        $admin = User::query()->orderBy('id')->first();
        if (! $admin) {
            $this->error('Needs an admin user. Run the default seeder first.');

            return self::FAILURE;
        }

        // Demo products go under the first category, or a DEMO category when the shop has none yet.
        $category = Category::query()->orderBy('id')->first() ?? Category::create([
            'name' => 'DEMO Category', 'slug' => 'demo-category', 'status' => 1, 'parent_id' => 0,
        ]);

        // Sales are booked to a DEMO customer of their own, never to a real customer.
        $customer = Customer::firstOrCreate(['phone' => '01000000000'], [
            'name' => 'DEMO Customer (not a real customer)', 'slug' => 'demo-customer',
            'password' => bcrypt(str()->random(32)), 'verify' => 1, 'status' => 'active',
        ]);

        $supplier = Supplier::firstOrCreate(['name' => 'DEMO Supplier (not a real business)'], [
            'address' => 'DEMO address, Dhaka', 'bin' => '123456789012', 'phone' => '01000000000',
        ]);

        $oil1 = $this->product('DEMO Mustard Oil 1 L', $category, 520, 400);
        $oil05 = $this->product('DEMO Mustard Oil 500 ml', $category, 280, 200);

        $this->createPurchase($code, $month, $supplier, $admin, [[$oil1, 10, 400], [$oil05, 20, 200]]);

        $sales = [
            ['day' => 5, 'lines' => [[$oil1, 2, 520]], 'bin' => null, 'buyer' => 'DEMO Buyer One'],
            ['day' => 12, 'lines' => [[$oil1, 1, 520], [$oil05, 3, 280]], 'bin' => '123456789012', 'buyer' => 'DEMO Buyer Two (registered)'],
            ['day' => 20, 'lines' => [[$oil05, 2, 280]], 'bin' => null, 'buyer' => 'DEMO Buyer Three'],
        ];
        foreach ($sales as $index => $sale) {
            $this->createSale($code, $index + 1, $month->copy()->day($sale['day'])->setTime(10, 0), $customer, $sale);
        }

        $this->info("Created DEMO purchase and 3 DEMO sales for {$month->format('Y-m')}. Stock is unchanged.");

        return self::SUCCESS;
    }

    private function product(string $name, Category $category, float $price, float $cost): Product
    {
        return Product::firstOrCreate(['name' => $name], [
            'slug' => 'demo-'.str()->slug($name), 'category_id' => $category->id,
            'product_code' => 'DEMO-'.str()->slug($name), 'purchase_price' => $cost, 'new_price' => $price,
            'stock' => 0, 'status' => 1, 'vat_rate' => 15,
        ]);
    }

    /** @param  list<array{0: Product, 1: int, 2: float}>  $lines */
    private function createPurchase(string $code, Carbon $month, Supplier $supplier, User $admin, array $lines): void
    {
        $total = array_sum(array_map(fn ($line) => $line[1] * $line[2], $lines));

        $purchase = Purchase::create([
            'supplier_id' => $supplier->id, 'invoice_no' => 'DEMO-PUR-'.$code,
            'purchase_date' => $month->copy()->day(2)->toDateString(),
            'total_qty' => array_sum(array_column($lines, 1)), 'subtotal' => $total, 'discount' => 0, 'shipping_cost' => 0,
            'grand_total' => $total, 'paid_amount' => $total, 'due_amount' => 0, 'note' => 'DEMO purchase',
            'created_by' => $admin->id,
        ]);

        foreach ($lines as [$product, $qty, $cost]) {
            PurchaseItem::create([
                'purchase_id' => $purchase->id, 'product_id' => $product->id, 'qty' => $qty,
                'unit_cost' => $cost, 'line_total' => $qty * $cost,
            ]);
        }
    }

    /** @param  array{day: int, lines: list<array{0: Product, 1: int, 2: float}>, bin: ?string, buyer: string}  $sale */
    private function createSale(string $code, int $number, Carbon $at, Customer $customer, array $sale): void
    {
        $total = array_sum(array_map(fn ($line) => $line[1] * $line[2], $sale['lines']));

        $order = Order::create([
            'invoice_id' => sprintf('DEMO-INV-%s-%02d', $code, $number),
            'mushak_invoice_no' => sprintf('DEMO-6.2-%s-%02d', $code, $number),
            'amount' => $total, 'discount' => 0, 'shipping_charge' => 0,
            'customer_id' => $customer->id, 'order_status' => 'completed',
            'buyer_bin' => $sale['bin'], 'created_at' => $at,
        ]);

        Shipping::create([
            'order_id' => $order->id, 'customer_id' => $customer->id, 'name' => $sale['buyer'],
            'phone' => '01000000000', 'address' => 'DEMO address, Dhaka', 'area' => 'DEMO',
        ]);

        foreach ($sale['lines'] as [$product, $qty, $price]) {
            OrderDetails::create([
                'order_id' => $order->id, 'product_id' => $product->id, 'product_name' => $product->name,
                'sale_price' => $price, 'qty' => $qty, 'purchase_price' => $product->purchase_price,
            ]);
        }
    }
}
