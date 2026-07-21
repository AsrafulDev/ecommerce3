<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\SupplierWarranty;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierWarrantyFactory extends Factory
{
    protected $model = SupplierWarranty::class;

    public function definition(): array
    {
        $days = fake()->randomElement([30, 60, 90, 180, 365, 0]);
        $start = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'purchase_item_id'  => PurchaseItem::factory(),
            'product_id'        => Product::factory(),
            'supplier_id'       => Supplier::factory(),
            'warranty_days'     => $days,
            'warranty_start_date' => $start,
            'warranty_end_date' => $days > 0 ? now()->parse($start)->addDays($days) : null,
            'warranty_type'     => 'supplier_warranty',
            'is_transferable'   => true,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn() => [
            'warranty_start_date' => now()->subDays(200),
            'warranty_end_date'   => now()->subDays(20),
            'warranty_days'       => 180,
        ]);
    }

    public function noWarranty(): static
    {
        return $this->state(fn() => [
            'warranty_days'       => 0,
            'warranty_start_date' => null,
            'warranty_end_date'   => null,
        ]);
    }
}
