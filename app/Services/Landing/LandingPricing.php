<?php

namespace App\Services\Landing;

use App\Models\Coupon;
use App\Models\LandingTemplate;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Validity, extra discount and the landing's coupon (plan 4.6 and D4–D6).
 *
 * One rule decides the discount on a landing order: the extra discount and the landing's
 * coupon are compared, and the larger reduction applies. They never stack.
 */
final class LandingPricing
{
    /** Active, and inside the validity window when one is set (D6). */
    public static function isOpen(LandingTemplate $landing, ?Carbon $at = null): bool
    {
        $at ??= now();

        return $landing->status
            && ($landing->valid_from === null || $at->gte($landing->valid_from))
            && ($landing->valid_until === null || $at->lte($landing->valid_until));
    }

    /** The extra discount on a line amount: a percent or a fixed sum, never more than the line. */
    public static function extraDiscount(LandingTemplate $landing, float $amount): float
    {
        if ($landing->extra_discount_type === null || (float) $landing->extra_discount_value <= 0) {
            return 0.0;
        }

        $discount = $landing->extra_discount_type === 'percent'
            ? $amount * ((float) $landing->extra_discount_value / 100)
            : (float) $landing->extra_discount_value;

        return round(min($discount, $amount), 2);
    }

    /**
     * The landing's coupon on a line amount, or 0 when it cannot be used now. The checks match
     * the shop's coupon rules: active, inside its dates, above the minimum, and under its use limit.
     */
    public static function couponDiscount(?Coupon $coupon, float $amount, ?Carbon $at = null): float
    {
        if ($coupon === null || $coupon->status != 1) {
            return 0.0;
        }

        $today = ($at ?? now())->copy()->startOfDay();
        if (($coupon->valid_from && $coupon->valid_from->startOfDay()->gt($today))
            || ($coupon->valid_to && $coupon->valid_to->startOfDay()->lt($today))) {
            return 0.0;
        }

        if ($coupon->min_purchase && $amount < (float) $coupon->min_purchase) {
            return 0.0;
        }

        if ((int) $coupon->max_uses > 0 && (int) $coupon->used_count >= (int) $coupon->max_uses) {
            return 0.0;
        }

        $discount = $coupon->type === 'percent'
            ? $amount * ((float) $coupon->value / 100)
            : (float) $coupon->value;

        return round(min($discount, $amount), 2);
    }

    /**
     * The discount that applies to a landing order: the larger of the extra discount and the coupon.
     *
     * @return array{coupon: ?Coupon, discount: float}
     */
    public static function best(LandingTemplate $landing, float $amount, ?Carbon $at = null): array
    {
        $coupon = $landing->coupon_id ? Coupon::find($landing->coupon_id) : null;
        $couponAmount = self::couponDiscount($coupon, $amount, $at);
        $extra = self::extraDiscount($landing, $amount);

        if ($couponAmount > 0 && $couponAmount >= $extra) {
            return ['coupon' => $coupon, 'discount' => $couponAmount];
        }

        return ['coupon' => null, 'discount' => $extra];
    }

    /**
     * Create the landing's own coupon (D4): a unique code, limited to the landing's validity window.
     * Only the landing can use it: the shop refuses codes that belong to a landing page.
     */
    public static function issueCoupon(LandingTemplate $landing, string $type, float $value): Coupon
    {
        $code = 'LP' . strtoupper(Str::random(6));
        while (Coupon::where('code', $code)->exists()) {
            $code = 'LP' . strtoupper(Str::random(6));
        }

        $coupon = Coupon::create([
            'code' => $code,
            'type' => $type,
            'value' => $value,
            'min_purchase' => 0,
            'valid_from' => $landing->valid_from?->toDateString(),
            'valid_to' => $landing->valid_until?->toDateString(),
            'max_uses' => 0,
            'used_count' => 0,
            'status' => 1,
        ]);

        $landing->update(['coupon_id' => $coupon->id]);

        return $coupon;
    }
}
