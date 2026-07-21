<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductWarrantyTier;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductWarrantyTierFactory extends Factory
{
    protected $model = ProductWarrantyTier::class;

    public function definition(): array
    {
        return [
            'product_id'      => Product::factory(),
            'tier_name'       => 'Standard Warranty',
            'warranty_type'   => 'supplier_warranty',
            'warranty_days'   => 180,
            'price'           => 500.00,
            'additional_cost' => 0,
            'is_active'       => true,
            'sort_order'      => 1,
        ];
    }

    public function noWarranty(): static
    {
        return $this->state(fn() => [
            'tier_name'     => 'No Warranty',
            'warranty_type' => 'none',
            'warranty_days' => 0,
            'price'         => 450.00,
            'sort_order'    => 0,
        ]);
    }

    public function extended(): static
    {
        return $this->state(fn() => [
            'tier_name'     => 'Extended Warranty',
            'warranty_type' => 'extended_warranty',
            'warranty_days' => 360,
            'price'         => 550.00,
            'sort_order'    => 2,
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn() => ['is_active' => false]);
    }
}
