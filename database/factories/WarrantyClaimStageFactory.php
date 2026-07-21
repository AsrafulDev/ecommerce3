<?php

namespace Database\Factories;

use App\Models\WarrantyClaimStage;
use App\Models\WarrantyClaim;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarrantyClaimStageFactory extends Factory
{
    protected $model = WarrantyClaimStage::class;

    public function definition(): array
    {
        return [
            'warranty_claim_id' => WarrantyClaim::factory(),
            'stage'             => fake()->randomElement(['submitted', 'document_verification', 'product_inspection', 'repair']),
            'status'            => fake()->randomElement(['pending', 'completed']),
            'started_at'        => now()->subHours(fake()->numberBetween(1, 72)),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn() => ['status' => 'pending', 'completed_at' => null]);
    }

    public function completed(): static
    {
        return $this->state(fn() => [
            'status'       => 'completed',
            'completed_at' => now(),
        ]);
    }
}
