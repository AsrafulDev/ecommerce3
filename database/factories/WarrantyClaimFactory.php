<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\WarrantyClaim;
use App\Models\WarrantySale;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarrantyClaimFactory extends Factory
{
    protected $model = WarrantyClaim::class;

    public function definition(): array
    {
        return [
            'warranty_sale_id'  => WarrantySale::factory(),
            'customer_id'       => Customer::factory(),
            'order_id'          => Order::factory(),
            'product_id'        => Product::factory(),
            'claim_number'      => 'WCL-' . now()->format('Ymd') . '-' . strtoupper(fake()->lexify('?????')),
            'issue_description' => fake()->sentence(10),
            'issue_type'        => fake()->randomElement(['defective', 'damaged', 'not_working', 'missing_parts', 'other']),
            'attachments'       => [],
            'status'            => 'submitted',
            'claimed_at'        => now(),
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn() => ['status' => 'submitted', 'claimed_at' => now()]);
    }

    public function underReview(): static
    {
        return $this->state(fn() => ['status' => 'under_review']);
    }

    public function approved(): static
    {
        return $this->state(fn() => ['status' => 'approved']);
    }

    public function inService(): static
    {
        return $this->state(fn() => ['status' => 'in_service']);
    }

    public function resolved(): static
    {
        return $this->state(fn() => [
            'status'      => 'resolved',
            'resolved_at' => now(),
            'resolution'  => 'Product repaired and returned.',
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn() => [
            'status'           => 'rejected',
            'resolved_at'      => now(),
            'rejection_reason' => 'Warranty terms not met.',
        ]);
    }
}
