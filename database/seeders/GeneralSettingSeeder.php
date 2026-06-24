<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GeneralSettingSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('general_settings')->count() === 0) {
            DB::table('general_settings')->insert([
                'name' => 'Ecommerce3',
                'white_logo' => 'public/assets/images/CurlBazar.png',
                'dark_logo' => 'public/assets/images/CurlBazar.png',
                'favicon' => 'public/favicon.ico',
                'copyright' => '© 2026 Ecommerce3. All rights reserved.',
                'status' => 1,
                'show_all_products' => 1,
                'show_category_wise_products' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
