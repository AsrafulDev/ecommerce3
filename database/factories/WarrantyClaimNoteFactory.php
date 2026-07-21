<?php

namespace Database\Factories;

use App\Models\WarrantyClaimNote;
use App\Models\WarrantyClaim;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarrantyClaimNoteFactory extends Factory
{
    protected $model = WarrantyClaimNote::class;

    public function definition(): array
    {
        return [
            'warranty_claim_id' => WarrantyClaim::factory(),
            'user_id'           => User::factory(),
            'note'              => fake()->sentence(),
        ];
    }
}
