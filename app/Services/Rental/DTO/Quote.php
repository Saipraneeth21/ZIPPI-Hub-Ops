<?php

namespace App\Services\Rental\DTO;

use App\Support\Money;

/** Immutable result of a pricing calculation. All amounts are Money (paise). */
final class Quote
{
    public function __construct(
        public readonly string $durationType,
        public readonly float $computedUnits,
        public readonly Money $baseAmount,
        public readonly Money $taxAmount,
        public readonly Money $platformFee,
        public readonly Money $discountAmount,
        public readonly Money $depositAmount,
        public readonly Money $totalAmount,
        public readonly ?string $couponCode = null,
    ) {}

    public function toArray(): array
    {
        return [
            'duration_type' => $this->durationType,
            'computed_units' => $this->computedUnits,
            'base_amount' => $this->baseAmount->paise,
            'tax_amount' => $this->taxAmount->paise,
            'platform_fee' => $this->platformFee->paise,
            'discount_amount' => $this->discountAmount->paise,
            'deposit_amount' => $this->depositAmount->paise,
            'total_amount' => $this->totalAmount->paise,
            'currency' => 'INR',
            'coupon_code' => $this->couponCode,
        ];
    }
}
