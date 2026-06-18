<?php

namespace App\Services\Rental;

use App\Models\Rental\Bike;
use App\Models\Rental\Booking;
use App\Models\User;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Booking lifecycle orchestration: create (with hold + availability lock),
 * cancel (policy refund), unlock (start ride), return (complete + auto deposit
 * refund). See APIs/03-Rental-Booking-APIs.md and Database/03-Data-Relationships.md.
 */
class BookingService
{
    public function __construct(
        private readonly PricingService $pricing,
        private readonly AvailabilityService $availability,
        private readonly PaymentService $payments,
        private readonly RefundService $refunds,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Create a pending booking + hold + gateway order.
     * @return array{booking: Booking, payment: \App\Models\Rental\Payment}
     */
    public function create(User $user, array $data, string $idempotencyKey): array
    {
        if (! $user->isKycApproved()) {
            throw new RuntimeException('KYC must be approved before booking.');
        }

        $bike = Bike::with('pricing')->findOrFail($data['bike_id']);
        $start = Carbon::parse($data['start_at']);
        $end = Carbon::parse($data['end_at']);

        $quote = $this->pricing->quote(
            $bike, $data['duration_type'], $start, $end, $data['coupon_code'] ?? null, $user->id
        );

        $booking = DB::transaction(function () use ($user, $bike, $data, $start, $end, $quote) {
            if (! $this->availability->lockAndCheck($bike->id, $start, $end)) {
                throw new RuntimeException('Bike is not available for the selected window.');
            }

            $couponId = null;
            if ($quote->couponCode) {
                $couponId = \App\Models\Rental\Coupon::where('code', $quote->couponCode)->value('id');
            }

            $booking = Booking::create([
                'booking_code' => 'TMP',
                'user_id' => $user->id,
                'bike_id' => $bike->id,
                'pickup_hub_id' => $data['pickup_hub_id'] ?? $bike->hub_id,
                'return_hub_id' => $data['return_hub_id'] ?? $bike->hub_id,
                'city_id' => $bike->city_id,
                'duration_type' => $quote->durationType,
                'start_at' => $start,
                'end_at' => $end,
                'computed_units' => $quote->computedUnits,
                'base_amount' => $quote->baseAmount->paise,
                'tax_amount' => $quote->taxAmount->paise,
                'platform_fee' => $quote->platformFee->paise,
                'discount_amount' => $quote->discountAmount->paise,
                'deposit_amount' => $quote->depositAmount->paise,
                'total_amount' => $quote->totalAmount->paise,
                'coupon_id' => $couponId,
                'status' => 'pending',
                'hold_expires_at' => Carbon::now()->addMinutes((int) config('rental.hold_ttl_minutes')),
            ]);
            $booking->update(['booking_code' => 'ZRP-' . $booking->id]);
            $booking->statusHistory()->create(['to_status' => 'pending', 'note' => 'Booking created']);

            return $booking;
        });

        $payment = $this->payments->createOrderForBooking($booking, $idempotencyKey);

        return ['booking' => $booking, 'payment' => $payment];
    }

    public function cancel(Booking $booking, string $reason, ?int $adminId = null, bool $overridePolicy = false): array
    {
        if (! in_array($booking->status, ['pending', 'confirmed'], true)) {
            throw new RuntimeException('Only upcoming bookings can be cancelled.');
        }

        $rentalRefundFraction = $overridePolicy ? 1.0 : $this->cancellationFraction($booking->start_at);
        $rentalRefundable = Money::ofPaise((int) $booking->base_amount)
            ->subtract(Money::ofPaise((int) $booking->discount_amount))
            ->add(Money::ofPaise((int) $booking->tax_amount))
            ->multiply($rentalRefundFraction);
        $deposit = Money::ofPaise((int) $booking->deposit_amount);

        $from = $booking->status;
        $booking->update(['status' => 'cancelled', 'cancellation_reason' => $reason]);
        $booking->statusHistory()->create([
            'from_status' => $from, 'to_status' => 'cancelled', 'changed_by' => $adminId, 'note' => $reason,
        ]);

        // Only refund money that was actually captured.
        $captured = $booking->payments()->where('status', 'captured')->exists();
        $rentalRefund = null;
        $depositRefund = null;
        if ($captured) {
            if ($rentalRefundable->paise > 0) {
                $rentalRefund = $this->refunds->process(
                    $booking, 'cancellation', $rentalRefundable, Money::zero(),
                    'Cancellation refund', 'wallet', "cancel_rental_{$booking->id}"
                );
            }
            if ($deposit->paise > 0) {
                $depositRefund = $this->refunds->process(
                    $booking, 'deposit', $deposit, Money::zero(),
                    'Deposit refund on cancellation', 'wallet', "cancel_deposit_{$booking->id}"
                );
            }
        }

        $this->notifications->notify(
            $booking->user_id, 'booking', 'Booking cancelled',
            "Booking {$booking->booking_code} was cancelled.",
            ['deep_link' => "zippi://bookings/{$booking->id}"]
        );

        return [
            'status' => 'cancelled',
            'rental_refund' => $rentalRefund?->amount ?? 0,
            'deposit_refund' => $depositRefund?->amount ?? 0,
        ];
    }

    public function unlock(Booking $booking): Booking
    {
        if ($booking->status !== 'confirmed') {
            throw new RuntimeException('Only confirmed bookings can be unlocked.');
        }
        $from = $booking->status;
        $booking->update(['status' => 'active', 'actual_start_at' => now()]);
        $booking->statusHistory()->create(['from_status' => $from, 'to_status' => 'active', 'note' => 'Bike unlocked']);
        $booking->bike()->update(['status' => 'booked']);

        $this->notifications->notify(
            $booking->user_id, 'booking', 'Ride started',
            "Your ride for {$booking->booking_code} has started.",
            ['deep_link' => "zippi://bookings/{$booking->id}/tracking"]
        );
        return $booking->fresh();
    }

    public function returnBike(Booking $booking, ?int $returnHubId = null, ?int $adminId = null): array
    {
        if ($booking->status !== 'active') {
            throw new RuntimeException('Only active rentals can be returned.');
        }

        $now = Carbon::now();
        $penalty = $this->latePenalty($booking->end_at, $now);

        $from = $booking->status;
        $booking->update([
            'status' => 'completed',
            'actual_end_at' => $now,
            'late_penalty' => $penalty->paise,
            'return_hub_id' => $returnHubId ?? $booking->return_hub_id,
        ]);
        $booking->statusHistory()->create([
            'from_status' => $from, 'to_status' => 'completed', 'changed_by' => $adminId,
            'note' => $penalty->isZero() ? 'Returned on time' : "Late penalty {$penalty->paise} paise",
        ]);
        $booking->bike()->update(['status' => 'available']);

        // Auto-refund deposit minus late penalty (deductions).
        $deposit = Money::ofPaise((int) $booking->deposit_amount);
        $refund = null;
        if ($deposit->paise > 0) {
            $refund = $this->refunds->process(
                $booking, 'deposit', $deposit, $penalty,
                $penalty->isZero() ? null : 'Late return penalty', 'wallet', "return_deposit_{$booking->id}"
            );
        }

        $this->notifications->notify(
            $booking->user_id, 'booking', 'Ride completed',
            "Ride {$booking->booking_code} completed. Deposit refund initiated.",
            ['deep_link' => "zippi://bookings/{$booking->id}"]
        );

        return [
            'status' => 'completed',
            'late_penalty' => $penalty->paise,
            'deposit_refund' => $refund?->amount ?? 0,
        ];
    }

    private function cancellationFraction(CarbonInterface $startAt): float
    {
        $hoursBefore = max(0, Carbon::now()->diffInMinutes($startAt, false) / 60);
        foreach (config('rental.cancellation_tiers') as $tier) {
            if ($hoursBefore >= $tier['min_hours_before']) {
                return (float) $tier['rental_refund_fraction'];
            }
        }
        return 0.0;
    }

    private function latePenalty(CarbonInterface $endAt, CarbonInterface $actualEnd): Money
    {
        if ($actualEnd->lessThanOrEqualTo($endAt)) {
            return Money::zero();
        }
        $lateHours = (int) ceil($endAt->diffInMinutes($actualEnd) / 60);
        return Money::ofPaise($lateHours * (int) config('rental.late_penalty_per_hour_paise'));
    }
}
