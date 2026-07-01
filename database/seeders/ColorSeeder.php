<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ColorSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding default colors...');

        if (DB::table('colors')->count() > 0) {
            $this->command->info('- Colors already exist, skipping.');
            return;
        }

        $colors = [
            ['colorName' => 'Red',    'color' => '#FF0000', 'status' => '1'],
            ['colorName' => 'Maroon', 'color' => '#800000', 'status' => '1'],
            ['colorName' => 'Pink',   'color' => '#FFC0CB', 'status' => '1'],
            ['colorName' => 'Orange', 'color' => '#FFA500', 'status' => '1'],
            ['colorName' => 'Yellow', 'color' => '#FFFF00', 'status' => '1'],
            ['colorName' => 'Gold',   'color' => '#FFD700', 'status' => '1'],
            ['colorName' => 'Green',  'color' => '#008000', 'status' => '1'],
            ['colorName' => 'Lime',   'color' => '#00FF00', 'status' => '1'],
            ['colorName' => 'Teal',   'color' => '#008080', 'status' => '1'],
            ['colorName' => 'Blue',   'color' => '#0000FF', 'status' => '1'],
            ['colorName' => 'Navy',   'color' => '#000080', 'status' => '1'],
            ['colorName' => 'Sky Blue','color'=> '#87CEEB', 'status' => '1'],
            ['colorName' => 'Purple', 'color' => '#800080', 'status' => '1'],
            ['colorName' => 'Violet', 'color' => '#8B00FF', 'status' => '1'],
            ['colorName' => 'Black',  'color' => '#000000', 'status' => '1'],
            ['colorName' => 'White',  'color' => '#FFFFFF', 'status' => '1'],
            ['colorName' => 'Gray',   'color' => '#808080', 'status' => '1'],
            ['colorName' => 'Silver', 'color' => '#C0C0C0', 'status' => '1'],
            ['colorName' => 'Brown',  'color' => '#A52A2A', 'status' => '1'],
            ['colorName' => 'Beige',  'color' => '#F5F5DC', 'status' => '1'],
            ['colorName' => 'Coral',  'color' => '#FF7F50', 'status' => '1'],
            ['colorName' => 'Cyan',   'color' => '#00FFFF', 'status' => '1'],
            ['colorName' => 'Magenta','color' => '#FF00FF', 'status' => '1'],
            ['colorName' => 'Olive',  'color' => '#808000', 'status' => '1'],
            ['colorName' => 'Indigo', 'color' => '#4B0082', 'status' => '1'],
        ];

        foreach ($colors as $color) {
            $color['created_at'] = now();
            $color['updated_at'] = now();
            DB::table('colors')->insert($color);
        }

        $this->command->info('- ' . count($colors) . ' default colors created.');
    }
}
