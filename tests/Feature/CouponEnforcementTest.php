<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Coupon;
use Illuminate\Support\Facades\DB;

class CouponEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_coupon_lock_prevents_overuse()
    {
        $coupon = Coupon::create([
            'code' => 'TEST10',
            'status' => 1,
            'type' => 'flat',
            'value' => 10,
            'max_uses' => 1,
            'used_count' => 0,
        ]);

        // First reservation should succeed
        DB::transaction(function () use ($coupon) {
            $c = Coupon::where('code', $coupon->code)
                ->where('status', 1)
                ->where(function ($q) {
                    $q->where('max_uses', 0)->orWhereColumn('used_count', '<', 'max_uses');
                })
                ->lockForUpdate()
                ->first();

            $this->assertNotNull($c);
            $c->increment('used_count');
        });

        $coupon->refresh();
        $this->assertEquals(1, $coupon->used_count);

        // Second reservation should return null (exhausted)
        $found = null;
        DB::transaction(function () use ($coupon, &$found) {
            $found = Coupon::where('code', $coupon->code)
                ->where('status', 1)
                ->where(function ($q) {
                    $q->where('max_uses', 0)->orWhereColumn('used_count', '<', 'max_uses');
                })
                ->lockForUpdate()
                ->first();
        });

        $this->assertNull($found);
    }
}
