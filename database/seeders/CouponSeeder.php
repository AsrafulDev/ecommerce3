<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding coupons...');

        if (DB::table('coupons')->count() > 0) {
            $this->command->info('- Coupons already exist, skipping.');
            return;
        }

        $coupons = [
            // ── Percentage discounts ──
            [
                'code'         => 'SAVE10',
                'type'         => 'percent',
                'value'        => 10,
                'min_purchase' => 500,
                'valid_from'   => now()->toDateString(),
                'valid_to'     => now()->addDays(30)->toDateString(),
                'max_uses'     => 100,
                'used_count'   => 0,
                'status'       => 1,
            ],
            [
                'code'         => 'SUMMER15',
                'type'         => 'percent',
                'value'        => 15,
                'min_purchase' => 1000,
                'valid_from'   => now()->toDateString(),
                'valid_to'     => now()->addDays(15)->toDateString(),
                'max_uses'     => 50,
                'used_count'   => 0,
                'status'       => 1,
            ],
            [
                'code'         => 'FLAT20',
                'type'         => 'percent',
                'value'        => 20,
                'min_purchase' => 2000,
                'valid_from'   => now()->toDateString(),
                'valid_to'     => now()->addDays(60)->toDateString(),
                'max_uses'     => 200,
                'used_count'   => 0,
                'status'       => 1,
            ],
            [
                'code'         => 'BIG25',
                'type'         => 'percent',
                'value'        => 25,
                'min_purchase' => 3000,
                'valid_from'   => now()->toDateString(),
                'valid_to'     => now()->addDays(7)->toDateString(),
                'max_uses'     => 30,
                'used_count'   => 0,
                'status'       => 1,
            ],
            [
                'code'         => 'MEGA30',
                'type'         => 'percent',
                'value'        => 30,
                'min_purchase' => 5000,
                'valid_from'   => now()->toDateString(),
                'valid_to'     => now()->addDays(10)->toDateString(),
                'max_uses'     => 20,
                'used_count'   => 0,
                'status'       => 1,
            ],
            [
                'code'         => 'HALFOFF',
                'type'         => 'percent',
                'value'        => 50,
                'min_purchase' => 10000,
                'valid_from'   => now()->toDateString(),
                'valid_to'     => now()->addDays(3)->toDateString(),
                'max_uses'     => 5,
                'used_count'   => 0,
                'status'       => 1,
            ],

            // ── Fixed amount discounts ──
            [
                'code'         => 'FLAT50',
                'type'         => 'fixed',
                'value'        => 50,
                'min_purchase' => 500,
                'valid_from'   => null,
                'valid_to'     => null,
                'max_uses'     => 0,
                'used_count'   => 0,
                'status'       => 1,
            ],
            [
                'code'         => 'FLAT100',
                'type'         => 'fixed',
                'value'        => 100,
                'min_purchase' => 1000,
                'valid_from'   => now()->toDateString(),
                'valid_to'     => now()->addDays(90)->toDateString(),
                'max_uses'     => 500,
                'used_count'   => 0,
                'status'       => 1,
            ],
            [
                'code'         => 'FLAT200',
                'type'         => 'fixed',
                'value'        => 200,
                'min_purchase' => 1500,
                'valid_from'   => now()->toDateString(),
                'valid_to'     => now()->addDays(45)->toDateString(),
                'max_uses'     => 100,
                'used_count'   => 0,
                'status'       => 1,
            ],
            [
                'code'         => 'FLAT500',
                'type'         => 'fixed',
                'value'        => 500,
                'min_purchase' => 3000,
                'valid_from'   => now()->toDateString(),
                'valid_to'     => now()->addDays(14)->toDateString(),
                'max_uses'     => 25,
                'used_count'   => 0,
                'status'       => 1,
            ],

            // ── No-minimum coupons ──
            [
                'code'         => 'WELCOME5',
                'type'         => 'percent',
                'value'        => 5,
                'min_purchase' => null,
                'valid_from'   => null,
                'valid_to'     => null,
                'max_uses'     => 1000,
                'used_count'   => 0,
                'status'       => 1,
            ],
            [
                'code'         => 'FIRST50',
                'type'         => 'fixed',
                'value'        => 50,
                'min_purchase' => null,
                'valid_from'   => now()->toDateString(),
                'valid_to'     => now()->addDays(30)->toDateString(),
                'max_uses'     => 200,
                'used_count'   => 0,
                'status'       => 1,
            ],
            [
                'code'         => 'FREESHIP',
                'type'         => 'fixed',
                'value'        => 60,
                'min_purchase' => null,
                'valid_from'   => null,
                'valid_to'     => null,
                'max_uses'     => 0,
                'used_count'   => 0,
                'status'       => 1,
            ],

            // ── Special / seasonal ──
            [
                'code'         => 'EID2026',
                'type'         => 'percent',
                'value'        => 15,
                'min_purchase' => 2000,
                'valid_from'   => now()->toDateString(),
                'valid_to'     => now()->addDays(5)->toDateString(),
                'max_uses'     => 300,
                'used_count'   => 0,
                'status'       => 1,
            ],
            [
                'code'         => 'NEWYEAR',
                'type'         => 'percent',
                'value'        => 10,
                'min_purchase' => 1000,
                'valid_from'   => now()->toDateString(),
                'valid_to'     => now()->addDays(30)->toDateString(),
                'max_uses'     => 500,
                'used_count'   => 0,
                'status'       => 1,
            ],
            [
                'code'         => 'BLACKFRIDAY',
                'type'         => 'percent',
                'value'        => 40,
                'min_purchase' => 5000,
                'valid_from'   => now()->toDateString(),
                'valid_to'     => now()->addDays(2)->toDateString(),
                'max_uses'     => 50,
                'used_count'   => 0,
                'status'       => 1,
            ],

            // ── Expired / soon-to-expire ──
            [
                'code'         => 'EXPIRED01',
                'type'         => 'percent',
                'value'        => 10,
                'min_purchase' => 500,
                'valid_from'   => now()->subDays(30)->toDateString(),
                'valid_to'     => now()->subDays(1)->toDateString(),
                'max_uses'     => 100,
                'used_count'   => 45,
                'status'       => 1,
            ],
            [
                'code'         => 'LASTCHANCE',
                'type'         => 'percent',
                'value'        => 20,
                'min_purchase' => 1000,
                'valid_from'   => now()->subDays(10)->toDateString(),
                'valid_to'     => now()->addDays(1)->toDateString(),
                'max_uses'     => 50,
                'used_count'   => 38,
                'status'       => 1,
            ],

            // ── Inactive coupon ──
            [
                'code'         => 'DISABLED',
                'type'         => 'percent',
                'value'        => 99,
                'min_purchase' => 100,
                'valid_from'   => null,
                'valid_to'     => null,
                'max_uses'     => 1,
                'used_count'   => 0,
                'status'       => 0,
            ],
        ];

        foreach ($coupons as $coupon) {
            $coupon['created_at'] = now();
            $coupon['updated_at'] = now();
            DB::table('coupons')->insert($coupon);
        }

        $this->command->info('- ' . count($coupons) . ' coupons seeded (percent + fixed, active + expired + inactive).');
    }
}
