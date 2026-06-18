<?php

namespace App\Services\Rental;

use App\Integrations\Contracts\PaymentGateway;
use App\Models\Rental\Booking;
use App\Models\Rental\Coupon;
use App\Models\Rental\Payment;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Payment orchestration. Creates gateway orders and confirms bookings on
 * verified payment. All confirmation is idempotent on the gateway payment id,
 * so duplicate client callbacks / webhooks never double-confirm.
 * See APIs/05-Payment-Wallet-APIs.md.
 */
class PaymentService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly CouponService $coupons,
        private readonly NotificationService $notifications,
    ) {}

    public function createOrderForBooking(Booking $booking, string $idempotencyKey): Payment
    {
        $existing = Payment::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        $order = $this->gateway->createOrder((int) $booking->total_amount, 'INR', $booking->booking_code);

        return Payment::create([
            'payment_reference' => 'PAY-' . Str::upper(Str::random(8)),
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'gateway' => 'razorpay',
            'gateway_order_id' => $order['order_id'],
            'amount' => $booking->total_amount,
            'currency' => 'INR',
            'status' => 'created',
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    /** Verify the checkout signature and confirm the booking. */
    public function verifyAndConfirm(string $paymentReference, string $orderId, string $paymentId, string $signature): Payment
    {
        $payment = Payment::where('payment_reference', $paymentReference)->firstOrFail();

        // Idempotent: if already captured, return as-is.
        if ($payment->status === 'captured') {
            return $payment;
        }

        if (! $this->gateway->verifySignature($orderId, $paymentId, $signature)) {
            throw new RuntimeException('Payment signature verification failed.');
        }

        return $this->confirm($payment, $paymentId, $signature);
    }

    /** Idempotent confirmation used by both client-verify and webhook paths. */
    public function confirm(Payment $payment, string $gatewayPaymentId, ?string $signature = null): Payment
    {
        return DB::transaction(function () use ($payment, $gatewayPaymentId, $signature) {
            $payment = Payment::where('id', $payment->id)->lockForUpdate()->first();
            if ($payment->status === 'captured') {
                return $payment;
            }

            $payment->update([
                'gateway_payment_id' => $gatewayPaymentId,
                'gateway_signature' => $signature,
                'status' => 'captured',
                'paid_at' => now(),
            ]);

            $booking = Booking::where('id', $payment->booking_id)->lockForUpdate()->first();
            if ($booking->status === 'pending') {
                $this->confirmBooking($booking);
            }

            return $payment;
        });
    }

    private function confirmBooking(Booking $booking): void
    {
        $from = $booking->status;
        $booking->update(['status' => 'confirmed', 'hold_expires_at' => null]);
        $booking->statusHistory()->create([
            'from_status' => $from, 'to_status' => 'confirmed', 'note' => 'Payment captured',
        ]);

        // Record coupon redemption now that the booking is real.
        if ($booking->coupon_id && $booking->discount_amount > 0) {
            $coupon = Coupon::find($booking->coupon_id);
            if ($coupon) {
                $this->coupons->recordRedemption(
                    $coupon, $booking->user_id, $booking->id, Money::ofPaise((int) $booking->discount_amount)
                );
            }
        }

        $this->notifications->notify(
            $booking->user_id, 'booking', 'Booking confirmed',
            "Your booking {$booking->booking_code} is confirmed.",
            ['deep_link' => "zippi://bookings/{$booking->id}", 'booking_id' => $booking->id]
        );
    }
}
