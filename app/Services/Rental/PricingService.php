<?php

namespace App\Services\Rental;

use App\Models\Rental\Bike;
use App\Services\Rental\DTO\Quote;
use App\Support\Money;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Pure pricing engine. Computes a server-authoritative quote from a bike, a
 * duration type and a window. No persistence. See APIs/03-Rental-Booking-APIs.md
 * (Pricing rules) and Database/03-Data-Relationships.md (computed fields).
 */
class PricingService
{
    public function __construct(private readonly CouponService $coupons) {}

    public function quote(
        Bike $bike,
        string $durationType,
        CarbonInterface $start,
        CarbonInterface $end,
        ?string $couponCode = null,
        ?int $userId = null,
    ): Quote {
        if (! $end->greaterThan($start)) {
            throw new InvalidArgumentException('End time must be after start time.');
        }

        $pricing = $bike->pricing;
        if (! $pricing) {
            throw new InvalidArgumentException('Bike has no active pricing.');
        }

        $hours = $start->diffInMinutes($end) / 60;

        [$units, $rate] = match ($durationType) {
            'hourly' => [
                max((float) ceil($hours), (float) $pricing->min_hours),
                (int) $pricing->hourly_rate,
            ],
            'daily' => [(float) (int) ceil($hours / 24), (int) $pricing->daily_rate],
            'weekly' => [(float) (int) ceil($hours / 24 / 7), (int) $pricing->weekly_rate],
            'monthly' => [(float) (int) ceil($hours / 24 / 30), (int) $pricing->monthly_rate],
            default => throw new InvalidArgumentException("Unknown duration type: {$durationType}"),
        };

        if ($rate <= 0) {
            throw new InvalidArgumentException("No {$durationType} rate configured for this bike.");
        }

        $base = Money::ofPaise((int) round($units * $rate));

        // Coupon discount applies to the base amount only.
        $discount = Money::zero();
        $appliedCoupon = null;
        if ($couponCode) {
            $result = $this->coupons->evaluate($couponCode, $base, $userId);
            if ($result['valid']) {
                $discount = $result['discount'];
                $appliedCoupon = $couponCode;
            }
        }

        $discountedBase = $base->subtract($discount);
        $tax = $discountedBase->percent((float) config('rental.gst_percent'));
        $platformFee = Money::ofPaise((int) config('rental.platform_fee_paise'));
        $deposit = Money::ofPaise((int) $bike->security_deposit_amount);

        $total = $discountedBase->add($tax)->add($platformFee)->add($deposit);

        return new Quote(
            durationType: $durationType,
            computedUnits: $units,
            baseAmount: $base,
            taxAmount: $tax,
            platformFee: $platformFee,
            discountAmount: $discount,
            depositAmount: $deposit,
            totalAmount: $total,
            couponCode: $appliedCoupon,
        );
    }
}
