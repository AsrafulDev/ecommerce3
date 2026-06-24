<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContactSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('contacts')->count() === 0) {
            DB::table('contacts')->insert([
                'hotline' => '8801519607646',
                'hotmail' => 'support@ecommerce3.com',
                'phone' => '8801519607646',
                'email' => 'support@ecommerce3.com',
                'address' => 'Dhaka, Bangladesh',
                'maplink' => '#',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
