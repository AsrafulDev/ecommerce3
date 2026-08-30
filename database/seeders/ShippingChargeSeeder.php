<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShippingChargeSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding default shipping charges...');

        if (DB::table('shipping_charges')->count() > 0) {
            $this->command->info('- Shipping charges already exist, skipping.');
            return;
        }

        $charges = [
            ['name' => 'Inside Dhaka',  'amount' => 70,  'status' => 1],
            ['name' => 'Outside Dhaka', 'amount' => 120, 'status' => 1],
        ];

        foreach ($charges as $charge) {
            $charge['created_at'] = now();
            $charge['updated_at'] = now();
            DB::table('shipping_charges')->insert($charge);
        }

        $this->command->info('- Seeded: Inside Dhaka 70 TK, Outside Dhaka 120 TK.');
    }
}
