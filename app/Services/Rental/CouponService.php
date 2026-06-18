<?php

namespace App\Services\Rental;

use App\Models\Rental\Coupon;
use App\Models\Rental\CouponRedemption;
use App\Support\Money;
use Illuminate\Support\Carbon;

/**
 * Coupon validation + discount calculation. Server-authoritative; enforces
 * validity window, minimum amount, and per-user / global usage limits.
 */
class CouponService
{
    /**
     * @return array{valid: bool, discount: Money, reason: ?string, coupon: ?Coupon}
     */
    public function evaluate(string $code, Money $baseAmount, ?int $userId): array
    {
        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon || ! $coupon->is_active) {
            return $this->fail('Coupon not found or inactive');
        }

        $now = Carbon::now();
        if ($now->lt($coupon->valid_from) || $now->gt($coupon->valid_to)) {
            return $this->fail('Coupon expired or not yet valid');
        }

        if ($coupon->min_booking_amount && $baseAmount->paise < $coupon->min_booking_amount) {
            return $this->fail('Minimum booking amount not met');
        }

        if ($coupon->usage_limit_total !== null && $coupon->used_count >= $coupon->usage_limit_total) {
            return $this->fail('Usage limit reached');
        }

        if ($userId !== null) {
            $userUses = CouponRedemption::where('coupon_id', $coupon->id)
                ->where('user_id', $userId)->count();
            if ($userUses >= $coupon->usage_limit_per_user) {
                return $this->fail('Usage limit reached for this user');
            }
        }

        $discount = $this->computeDiscount($coupon, $baseAmount);

        return ['valid' => true, 'discount' => $discount, 'reason' => null, 'coupon' => $coupon];
    }

    public function computeDiscount(Coupon $coupon, Money $baseAmount): Money
    {
        if ($coupon->type === 'percent') {
            // value stored as percentage (e.g. 10 => 10%)
            $discount = $baseAmount->percent((float) $coupon->value);
        } else {
            $discount = Money::ofPaise((int) $coupon->value);
        }

        if ($coupon->max_discount !== null && $discount->paise > $coupon->max_discount) {
            $discount = Money::ofPaise((int) $coupon->max_discount);
        }

        // Never discount more than the base.
        return $discount->paise > $baseAmount->paise ? $baseAmount : $discount;
    }

    public function recordRedemption(Coupon $coupon, int $userId, int $bookingId, Money $discount): void
    {
        CouponRedemption::create([
            'coupon_id' => $coupon->id,
            'user_id' => $userId,
            'booking_id' => $bookingId,
            'discount_amount' => $discount->paise,
        ]);
        $coupon->increment('used_count');
    }

    private function fail(string $reason): array
    {
        return ['valid' => false, 'discount' => Money::zero(), 'reason' => $reason, 'coupon' => null];
    }
}
