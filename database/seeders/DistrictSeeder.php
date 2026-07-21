<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DistrictSeeder extends Seeder
{
    public function run(): void
    {
        // Only seed if table is empty
        if (DB::table('districts')->count() > 0) {
            $this->command->info('Districts table already has data — skipping.');
            return;
        }

        $data = [
            // Dhaka Division
            ['district' => 'Dhaka', 'area_name' => 'Mirpur', 'shippingfee' => 70, 'partialpayment' => 0],
            ['district' => 'Dhaka', 'area_name' => 'Uttara', 'shippingfee' => 80, 'partialpayment' => 0],
            ['district' => 'Dhaka', 'area_name' => 'Gulshan', 'shippingfee' => 60, 'partialpayment' => 0],
            ['district' => 'Dhaka', 'area_name' => 'Dhanmondi', 'shippingfee' => 60, 'partialpayment' => 0],
            ['district' => 'Dhaka', 'area_name' => 'Mohammadpur', 'shippingfee' => 60, 'partialpayment' => 0],
            ['district' => 'Dhaka', 'area_name' => 'Motijheel', 'shippingfee' => 50, 'partialpayment' => 0],
            ['district' => 'Dhaka', 'area_name' => 'Banani', 'shippingfee' => 60, 'partialpayment' => 0],
            ['district' => 'Dhaka', 'area_name' => 'Bashundhara', 'shippingfee' => 80, 'partialpayment' => 0],
            ['district' => 'Gazipur', 'area_name' => 'Gazipur Sadar', 'shippingfee' => 120, 'partialpayment' => 0],
            ['district' => 'Gazipur', 'area_name' => 'Tongi', 'shippingfee' => 100, 'partialpayment' => 0],
            ['district' => 'Narayanganj', 'area_name' => 'Narayanganj Sadar', 'shippingfee' => 100, 'partialpayment' => 0],
            ['district' => 'Narayanganj', 'area_name' => 'Fatullah', 'shippingfee' => 100, 'partialpayment' => 0],
            ['district' => 'Savar', 'area_name' => 'Savar', 'shippingfee' => 100, 'partialpayment' => 0],
            ['district' => 'Savar', 'area_name' => 'Ashulia', 'shippingfee' => 120, 'partialpayment' => 0],

            // Chittagong Division
            ['district' => 'Chittagong', 'area_name' => 'Agrabad', 'shippingfee' => 130, 'partialpayment' => 0],
            ['district' => 'Chittagong', 'area_name' => 'Halishahar', 'shippingfee' => 130, 'partialpayment' => 0],
            ['district' => 'Chittagong', 'area_name' => 'Nasirabad', 'shippingfee' => 130, 'partialpayment' => 0],
            ['district' => 'Chittagong', 'area_name' => 'GEC Circle', 'shippingfee' => 130, 'partialpayment' => 0],
            ['district' => 'Cox\'s Bazar', 'area_name' => 'Cox\'s Bazar Sadar', 'shippingfee' => 150, 'partialpayment' => 0],
            ['district' => 'Comilla', 'area_name' => 'Comilla Sadar', 'shippingfee' => 130, 'partialpayment' => 0],
            ['district' => 'Feni', 'area_name' => 'Feni Sadar', 'shippingfee' => 130, 'partialpayment' => 0],

            // Rajshahi Division
            ['district' => 'Rajshahi', 'area_name' => 'Rajshahi Sadar', 'shippingfee' => 140, 'partialpayment' => 0],
            ['district' => 'Rajshahi', 'area_name' => 'Shaheb Bazar', 'shippingfee' => 140, 'partialpayment' => 0],
            ['district' => 'Bogra', 'area_name' => 'Bogra Sadar', 'shippingfee' => 150, 'partialpayment' => 0],
            ['district' => 'Pabna', 'area_name' => 'Pabna Sadar', 'shippingfee' => 140, 'partialpayment' => 0],

            // Khulna Division
            ['district' => 'Khulna', 'area_name' => 'Khulna Sadar', 'shippingfee' => 140, 'partialpayment' => 0],
            ['district' => 'Khulna', 'area_name' => 'Boyra', 'shippingfee' => 140, 'partialpayment' => 0],
            ['district' => 'Jessore', 'area_name' => 'Jessore Sadar', 'shippingfee' => 140, 'partialpayment' => 0],
            ['district' => 'Kushtia', 'area_name' => 'Kushtia Sadar', 'shippingfee' => 150, 'partialpayment' => 0],

            // Sylhet Division
            ['district' => 'Sylhet', 'area_name' => 'Sylhet Sadar', 'shippingfee' => 140, 'partialpayment' => 0],
            ['district' => 'Sylhet', 'area_name' => 'Ambarkhana', 'shippingfee' => 140, 'partialpayment' => 0],
            ['district' => 'Sylhet', 'area_name' => 'Zindabazar', 'shippingfee' => 140, 'partialpayment' => 0],
            ['district' => 'Moulvibazar', 'area_name' => 'Moulvibazar Sadar', 'shippingfee' => 150, 'partialpayment' => 0],

            // Barisal Division
            ['district' => 'Barisal', 'area_name' => 'Barisal Sadar', 'shippingfee' => 150, 'partialpayment' => 0],
            ['district' => 'Patuakhali', 'area_name' => 'Patuakhali Sadar', 'shippingfee' => 160, 'partialpayment' => 0],

            // Rangpur Division
            ['district' => 'Rangpur', 'area_name' => 'Rangpur Sadar', 'shippingfee' => 150, 'partialpayment' => 0],
            ['district' => 'Dinajpur', 'area_name' => 'Dinajpur Sadar', 'shippingfee' => 150, 'partialpayment' => 0],
            ['district' => 'Saidpur', 'area_name' => 'Saidpur', 'shippingfee' => 150, 'partialpayment' => 0],

            // Mymensingh Division
            ['district' => 'Mymensingh', 'area_name' => 'Mymensingh Sadar', 'shippingfee' => 130, 'partialpayment' => 0],
            ['district' => 'Jamalpur', 'area_name' => 'Jamalpur Sadar', 'shippingfee' => 140, 'partialpayment' => 0],
        ];

        foreach ($data as $i => $row) {
            DB::table('districts')->insert([
                'area_id'        => $i + 1,
                'area_name'      => $row['area_name'],
                'district'       => $row['district'],
                'shippingfee'    => $row['shippingfee'],
                'partialpayment' => $row['partialpayment'],
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        $this->command->info('Seeded ' . count($data) . ' district/area records!');
    }
}
