<?php
namespace Tests\Feature;

use App\Models\Rental\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesRentalData;
use Tests\TestCase;

class PaymentIdempotencyTest extends TestCase
{
    use RefreshDatabase, CreatesRentalData;

    public function test_duplicate_payment_verification_confirms_once(): void
    {
        $rider = $this->approvedRider();
        $bike = $this->aBike();
        Sanctum::actingAs($rider);

        $create = $this->withHeaders(['Idempotency-Key' => 'book-i'])
            ->postJson('/api/rental/v1/bookings', array_merge([
                'bike_id' => $bike->id, 'duration_type' => 'daily',
            ], $this->window()))->json('data');

        $orderId = $create['payment']['order_id'];
        $ref = $create['payment']['payment_reference'];
        $pid = 'pay_DUP';
        $sig = $this->signature($orderId, $pid);

        $payload = [
            'payment_reference' => $ref, 'razorpay_order_id' => $orderId,
            'razorpay_payment_id' => $pid, 'razorpay_signature' => $sig,
        ];

        $this->withHeaders(['Idempotency-Key' => 'pv1'])->postJson('/api/rental/v1/payments/verify', $payload)->assertOk();
        // Replay the same verification (duplicate webhook/callback)
        $this->withHeaders(['Idempotency-Key' => 'pv1'])->postJson('/api/rental/v1/payments/verify', $payload)->assertOk();

        // Exactly one captured payment, booking confirmed once.
        $this->assertSame(1, Payment::where('booking_id', $create['booking']['id'])->where('status', 'captured')->count());
        $this->assertDatabaseHas('rental_bookings', ['id' => $create['booking']['id'], 'status' => 'confirmed']);
        // Coupon-less booking: only one status-history 'confirmed' row.
        $this->assertSame(1, \App\Models\Rental\BookingStatusHistory::where('booking_id', $create['booking']['id'])->where('to_status', 'confirmed')->count());
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $rider = $this->approvedRider();
        $bike = $this->aBike();
        Sanctum::actingAs($rider);

        $create = $this->withHeaders(['Idempotency-Key' => 'book-bad'])
            ->postJson('/api/rental/v1/bookings', array_merge([
                'bike_id' => $bike->id, 'duration_type' => 'daily',
            ], $this->window()))->json('data');

        $this->withHeaders(['Idempotency-Key' => 'pbad'])->postJson('/api/rental/v1/payments/verify', [
            'payment_reference' => $create['payment']['payment_reference'],
            'razorpay_order_id' => $create['payment']['order_id'],
            'razorpay_payment_id' => 'pay_X',
            'razorpay_signature' => 'deadbeef',
        ])->assertStatus(422);

        $this->assertDatabaseHas('rental_bookings', ['id' => $create['booking']['id'], 'status' => 'pending']);
    }
}
