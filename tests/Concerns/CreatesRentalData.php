<?php
namespace Tests\Concerns;

use App\Integrations\Contracts\PaymentGateway;
use App\Models\Rental\Bike;
use App\Models\User;

trait CreatesRentalData
{
    protected function approvedRider(): User
    {
        return User::factory()->kycApproved()->create();
    }

    protected function aBike(int $deposit = 200000): Bike
    {
        return Bike::factory()->create(['security_deposit_amount' => $deposit]);
    }

    /** Compute a valid Razorpay checkout signature for the test gateway. */
    protected function signature(string $orderId, string $paymentId): string
    {
        /** @var \App\Integrations\Payment\RazorpayGateway $gw */
        $gw = app(PaymentGateway::class);
        return $gw->computeSignature($orderId, $paymentId);
    }

    /** Convenience: start/end ISO strings for a daily booking N days out. */
    protected function window(int $startInDays = 1, int $days = 2): array
    {
        return [
            'start_at' => now()->addDays($startInDays)->startOfHour()->toIso8601String(),
            'end_at' => now()->addDays($startInDays + $days)->startOfHour()->toIso8601String(),
        ];
    }
}
