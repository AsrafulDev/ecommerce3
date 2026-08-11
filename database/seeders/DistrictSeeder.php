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

        $rows = self::defaultRows();

        $now = now();
        foreach ($rows as $row) {
            DB::table('districts')->insert([
                'area_id'        => $row['area_id'],
                'area_name'      => $row['area_name'],
                'district'       => $row['district'],
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }

        $this->command->info('Seeded ' . count($rows) . ' district/area records (' . count(array_unique(array_column($rows, 'district'))) . ' districts)!');
    }

    /**
     * Build the default district/area rows (64 districts + extra city areas).
     * Used by run() and by the admin "Sync Default" action.
     */
    public static function defaultRows(): array
    {
        // ── All 64 districts of Bangladesh (one base area each) ──
        $districts64 = [
            // Dhaka Division (13)
            'Dhaka', 'Faridpur', 'Gazipur', 'Gopalganj', 'Kishoreganj', 'Madaripur',
            'Manikganj', 'Munshiganj', 'Narayanganj', 'Narsingdi', 'Rajbari',
            'Shariatpur', 'Tangail',
            // Mymensingh Division (4)
            'Mymensingh', 'Jamalpur', 'Netrokona', 'Sherpur',
            // Chattogram Division (11)
            'Chittagong', 'Bandarban', 'Brahmanbaria', 'Chandpur', 'Comilla',
            "Cox's Bazar", 'Feni', 'Khagrachhari', 'Lakshmipur', 'Noakhali', 'Rangamati',
            // Rajshahi Division (8)
            'Rajshahi', 'Bogra', 'Joypurhat', 'Naogaon', 'Natore', 'Pabna',
            'Sirajganj', 'Chapainawabganj',
            // Khulna Division (10)
            'Khulna', 'Bagerhat', 'Chuadanga', 'Jessore', 'Jhenaidah', 'Kushtia',
            'Magura', 'Meherpur', 'Narail', 'Satkhira',
            // Barishal Division (6)
            'Barisal', 'Barguna', 'Bhola', 'Jhalokathi', 'Patuakhali', 'Pirojpur',
            // Sylhet Division (4)
            'Sylhet', 'Habiganj', 'Moulvibazar', 'Sunamganj',
            // Rangpur Division (8)
            'Rangpur', 'Dinajpur', 'Gaibandha', 'Kurigram', 'Lalmonirhat',
            'Nilphamari', 'Panchagarh', 'Thakurgaon',
        ];

        // ── Extra areas for major cities (better checkout granularity) ──
        $extraAreas = [
            // Dhaka
            ['district' => 'Dhaka', 'area_name' => 'Mirpur'],
            ['district' => 'Dhaka', 'area_name' => 'Uttara'],
            ['district' => 'Dhaka', 'area_name' => 'Gulshan'],
            ['district' => 'Dhaka', 'area_name' => 'Dhanmondi'],
            ['district' => 'Dhaka', 'area_name' => 'Mohammadpur'],
            ['district' => 'Dhaka', 'area_name' => 'Motijheel'],
            ['district' => 'Dhaka', 'area_name' => 'Banani'],
            ['district' => 'Dhaka', 'area_name' => 'Bashundhara'],
            ['district' => 'Dhaka', 'area_name' => 'Savar'],
            ['district' => 'Dhaka', 'area_name' => 'Ashulia'],
            ['district' => 'Gazipur', 'area_name' => 'Gazipur Sadar'],
            ['district' => 'Gazipur', 'area_name' => 'Tongi'],
            ['district' => 'Narayanganj', 'area_name' => 'Narayanganj Sadar'],
            ['district' => 'Narayanganj', 'area_name' => 'Fatullah'],
            ['district' => 'Narsingdi', 'area_name' => 'Narsingdi Sadar'],
            ['district' => 'Tangail', 'area_name' => 'Tangail Sadar'],
            ['district' => 'Kishoreganj', 'area_name' => 'Kishoreganj Sadar'],
            ['district' => 'Madaripur', 'area_name' => 'Madaripur Sadar'],
            ['district' => 'Shariatpur', 'area_name' => 'Shariatpur Sadar'],
            ['district' => 'Munshiganj', 'area_name' => 'Munshiganj Sadar'],
            ['district' => 'Manikganj', 'area_name' => 'Manikganj Sadar'],
            ['district' => 'Faridpur', 'area_name' => 'Faridpur Sadar'],
            ['district' => 'Gopalganj', 'area_name' => 'Gopalganj Sadar'],
            ['district' => 'Narsingdi', 'area_name' => 'Belabo'],
            ['district' => 'Narsingdi', 'area_name' => 'Raipura'],
            ['district' => 'Tangail', 'area_name' => 'Kalihati'],
            ['district' => 'Tangail', 'area_name' => 'Bhuapur'],
            ['district' => 'Kishoreganj', 'area_name' => 'Bhairab'],
            ['district' => 'Kishoreganj', 'area_name' => 'Hossainpur'],
            ['district' => 'Madaripur', 'area_name' => 'Kalkini'],
            ['district' => 'Madaripur', 'area_name' => 'Rajoir'],
            ['district' => 'Shariatpur', 'area_name' => 'Bhedarganj'],
            ['district' => 'Shariatpur', 'area_name' => 'Damudya'],
            ['district' => 'Munshiganj', 'area_name' => 'Sreenagar'],
            ['district' => 'Munshiganj', 'area_name' => 'Lohajang'],
            ['district' => 'Manikganj', 'area_name' => 'Saturia'],
            ['district' => 'Manikganj', 'area_name' => 'Shibalaya'],
            ['district' => 'Faridpur', 'area_name' => 'Boalmari'],
            ['district' => 'Faridpur', 'area_name' => 'Alfadanga'],
            ['district' => 'Gopalganj', 'area_name' => 'Kashiani'],
            ['district' => 'Gopalganj', 'area_name' => 'Muksudpur'],
            // Chittagong
            ['district' => 'Chittagong', 'area_name' => 'Agrabad'],
            ['district' => 'Chittagong', 'area_name' => 'Halishahar'],
            ['district' => 'Chittagong', 'area_name' => 'Nasirabad'],
            ['district' => 'Chittagong', 'area_name' => 'GEC Circle'],
            ["district" => "Cox's Bazar", 'area_name' => "Cox's Bazar Sadar"],
            ['district' => 'Comilla', 'area_name' => 'Comilla Sadar'],
            ['district' => 'Feni', 'area_name' => 'Feni Sadar'],
            ['district' => 'Brahmanbaria', 'area_name' => 'Brahmanbaria Sadar'],
            ['district' => 'Noakhali', 'area_name' => 'Noakhali Sadar'],
            ['district' => 'Khagrachhari', 'area_name' => 'Khagrachhari Sadar'],
            ['district' => 'Rangamati', 'area_name' => 'Rangamati Sadar'],
            ['district' => 'Bandarban', 'area_name' => 'Bandarban Sadar'],
            // Rajshahi
            ['district' => 'Rajshahi', 'area_name' => 'Rajshahi Sadar'],
            ['district' => 'Rajshahi', 'area_name' => 'Shaheb Bazar'],
            ['district' => 'Bogra', 'area_name' => 'Bogra Sadar'],
            ['district' => 'Pabna', 'area_name' => 'Pabna Sadar'],
            ['district' => 'Natore', 'area_name' => 'Natore Sadar'],
            ['district' => 'Sirajganj', 'area_name' => 'Sirajganj Sadar'],
            ['district' => 'Naogaon', 'area_name' => 'Naogaon Sadar'],
            ['district' => 'Joypurhat', 'area_name' => 'Joypurhat Sadar'],
            ['district' => 'Chapainawabganj', 'area_name' => 'Chapainawabganj Sadar'],
            // Khulna
            ['district' => 'Khulna', 'area_name' => 'Khulna Sadar'],
            ['district' => 'Khulna', 'area_name' => 'Boyra'],
            ['district' => 'Jessore', 'area_name' => 'Jessore Sadar'],
            ['district' => 'Kushtia', 'area_name' => 'Kushtia Sadar'],
            ['district' => 'Jhenaidah', 'area_name' => 'Jhenaidah Sadar'],
            ['district' => 'Magura', 'area_name' => 'Magura Sadar'],
            ['district' => 'Bagerhat', 'area_name' => 'Bagerhat Sadar'],
            ['district' => 'Meherpur', 'area_name' => 'Meherpur Sadar'],
            ['district' => 'Narail', 'area_name' => 'Narail Sadar'],
            ['district' => 'Satkhira', 'area_name' => 'Satkhira Sadar'],
            // Sylhet
            ['district' => 'Sylhet', 'area_name' => 'Sylhet Sadar'],
            ['district' => 'Sylhet', 'area_name' => 'Ambarkhana'],
            ['district' => 'Sylhet', 'area_name' => 'Zindabazar'],
            ['district' => 'Moulvibazar', 'area_name' => 'Moulvibazar Sadar'],
            // Barishal
            ['district' => 'Barisal', 'area_name' => 'Barisal Sadar'],
            ['district' => 'Patuakhali', 'area_name' => 'Patuakhali Sadar'],
            ['district' => 'Bhola', 'area_name' => 'Bhola Sadar'],
            ['district' => 'Jhalokathi', 'area_name' => 'Jhalokathi Sadar'],
            ['district' => 'Pirojpur', 'area_name' => 'Pirojpur Sadar'],
            ['district' => 'Barguna', 'area_name' => 'Barguna Sadar'],
            // Rangpur
            ['district' => 'Rangpur', 'area_name' => 'Rangpur Sadar'],
            ['district' => 'Dinajpur', 'area_name' => 'Dinajpur Sadar'],
            ['district' => 'Thakurgaon', 'area_name' => 'Thakurgaon Sadar'],
            ['district' => 'Panchagarh', 'area_name' => 'Panchagarh Sadar'],
            ['district' => 'Kurigram', 'area_name' => 'Kurigram Sadar'],
            ['district' => 'Lalmonirhat', 'area_name' => 'Lalmonirhat Sadar'],
            ['district' => 'Nilphamari', 'area_name' => 'Nilphamari Sadar'],
            ['district' => 'Gaibandha', 'area_name' => 'Gaibandha Sadar'],
            // Mymensingh
            ['district' => 'Mymensingh', 'area_name' => 'Mymensingh Sadar'],
            ['district' => 'Jamalpur', 'area_name' => 'Jamalpur Sadar'],
            ['district' => 'Netrokona', 'area_name' => 'Netrokona Sadar'],
            ['district' => 'Sherpur', 'area_name' => 'Sherpur Sadar'],
        ];

        $rows = [];
        $i = 0;

        // Base area for each of the 64 districts (area_name = district name)
        foreach ($districts64 as $district) {
            $i++;
            $rows[] = [
                'area_id'        => $i,
                'area_name'      => $district,
                'district'       => $district,
            ];
        }

        // Extra detailed areas for major cities
        foreach ($extraAreas as $area) {
            $i++;
            $rows[] = [
                'area_id'        => $i,
                'area_name'      => $area['area_name'],
                'district'       => $area['district'],
            ];
        }

        return $rows;
    }
}
