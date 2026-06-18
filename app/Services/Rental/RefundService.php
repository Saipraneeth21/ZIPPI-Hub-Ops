<?php

namespace App\Services\Rental;

use App\Integrations\Contracts\PaymentGateway;
use App\Models\Rental\Booking;
use App\Models\Rental\Refund;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Refund processing. Idempotent on idempotency_key. Refunds default to the
 * wallet (credited via the ledger); deductions are subtracted from the amount.
 * See Database/03-Data-Relationships.md §2.5.
 */
class RefundService
{
    public function __construct(
        private readonly WalletService $wallet,
        private readonly NotificationService $notifications,
        private readonly PaymentGateway $gateway,
    ) {}

    /**
     * @param Money $amount gross amount before deductions
     */
    public function process(
        Booking $booking,
        string $type,
        Money $amount,
        Money $deductions,
        ?string $deductionNote = null,
        string $destination = 'wallet',
        ?string $idempotencyKey = null,
    ): Refund {
        $idempotencyKey ??= 'auto_' . $booking->id . '_' . $type;

        $existing = Refund::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing; // idempotent: never double-refund
        }

        $net = $amount->subtract($deductions);

        $refund = DB::transaction(function () use ($booking, $type, $net, $deductions, $deductionNote, $destination, $idempotencyKey) {
            $payment = $booking->payments()->where('status', 'captured')->first();

            $refund = Refund::create([
                'refund_reference' => 'REF-' . Str::upper(Str::random(8)),
                'booking_id' => $booking->id,
                'payment_id' => $payment?->id,
                'user_id' => $booking->user_id,
                'type' => $type,
                'amount' => $net->paise,
                'destination' => $destination,
                'deductions' => $deductions->paise,
                'deduction_note' => $deductionNote,
                'status' => 'pending',
                'idempotency_key' => $idempotencyKey,
            ]);

            if ($destination === 'wallet') {
                $this->wallet->credit(
                    $booking->user_id, $net, 'refund', $refund->id,
                    ucfirst($type) . " refund {$booking->booking_code}"
                );
            } elseif ($payment) {
                $result = $this->gateway->refund($payment->gateway_payment_id, $net->paise);
                $refund->gateway_refund_id = $result['refund_id'];
            }

            $refund->update(['status' => 'processed', 'processed_at' => now()]);

            return $refund;
        });

        $this->notifications->notify(
            $booking->user_id, 'refund', 'Refund processed',
            '₹' . number_format($refund->amount / 100, 2) . ' credited.',
            ['deep_link' => 'zippi://wallet', 'booking_id' => $booking->id]
        );

        return $refund;
    }
}
