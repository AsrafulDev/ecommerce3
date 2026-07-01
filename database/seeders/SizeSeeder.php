<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SizeSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding default sizes...');

        if (DB::table('sizes')->count() > 0) {
            $this->command->info('- Sizes already exist, skipping.');
            return;
        }

        $sizes = [
            // Apparel / Clothing sizes
            ['sizeName' => 'XS',   'status' => '1'],
            ['sizeName' => 'S',    'status' => '1'],
            ['sizeName' => 'M',    'status' => '1'],
            ['sizeName' => 'L',    'status' => '1'],
            ['sizeName' => 'XL',   'status' => '1'],
            ['sizeName' => 'XXL',  'status' => '1'],
            ['sizeName' => '3XL',  'status' => '1'],
            ['sizeName' => '4XL',  'status' => '1'],
            ['sizeName' => '5XL',  'status' => '1'],

            // Numeric sizes
            ['sizeName' => '28',   'status' => '1'],
            ['sizeName' => '30',   'status' => '1'],
            ['sizeName' => '32',   'status' => '1'],
            ['sizeName' => '34',   'status' => '1'],
            ['sizeName' => '36',   'status' => '1'],
            ['sizeName' => '38',   'status' => '1'],
            ['sizeName' => '40',   'status' => '1'],
            ['sizeName' => '42',   'status' => '1'],
            ['sizeName' => '44',   'status' => '1'],

            // Shoe sizes
            ['sizeName' => 'UK 5',  'status' => '1'],
            ['sizeName' => 'UK 6',  'status' => '1'],
            ['sizeName' => 'UK 7',  'status' => '1'],
            ['sizeName' => 'UK 8',  'status' => '1'],
            ['sizeName' => 'UK 9',  'status' => '1'],
            ['sizeName' => 'UK 10', 'status' => '1'],
            ['sizeName' => 'UK 11', 'status' => '1'],

            // Free-size / One-size
            ['sizeName' => 'Free Size', 'status' => '1'],
        ];

        foreach ($sizes as $size) {
            $size['created_at'] = now();
            $size['updated_at'] = now();
            DB::table('sizes')->insert($size);
        }

        $this->command->info('- ' . count($sizes) . ' default sizes created.');
    }
}
