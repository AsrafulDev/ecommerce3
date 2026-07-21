<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\WarrantySale;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarrantySaleFactory extends Factory
{
    protected $model = WarrantySale::class;

    public function definition(): array
    {
        $days  = fake()->randomElement([0, 60, 90, 180, 360]);
        $start = fake()->optional()->dateTimeBetween('-3 months');

        return [
            'order_id'                 => Order::factory(),
            'order_detail_id'          => OrderDetails::factory(),
            'product_warranty_tier_id' => null,
            'customer_id'              => Customer::factory(),
            'product_id'               => Product::factory(),
            'warranty_type'            => $days > 0 ? 'supplier_warranty' : 'none',
            'warranty_days'            => $days,
            'warranty_start_date'      => $start,
            'warranty_end_date'        => $start && $days > 0 ? now()->parse($start)->addDays($days) : null,
            'warranty_price'           => fake()->randomFloat(2, 300, 3000),
            'status'                   => 'active',
        ];
    }

    public function active(): static
    {
        return $this->state(fn() => [
            'warranty_start_date' => now()->subDays(30),
            'warranty_end_date'   => now()->addDays(150),
            'warranty_days'       => 180,
            'status'              => 'active',
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn() => [
            'warranty_start_date' => now()->subDays(200),
            'warranty_end_date'   => now()->subDays(20),
            'warranty_days'       => 180,
            'status'              => 'expired',
        ]);
    }

    public function noWarranty(): static
    {
        return $this->state(fn() => [
            'warranty_type'       => 'none',
            'warranty_days'       => 0,
            'warranty_start_date' => null,
            'warranty_end_date'   => null,
            'status'              => 'active',
        ]);
    }
}
