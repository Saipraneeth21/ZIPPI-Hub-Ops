<?php
namespace Tests\Feature;

use App\Models\Rental\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesRentalData;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase, CreatesRentalData;

    public function test_full_loop_book_pay_unlock_return_refund(): void
    {
        $rider = $this->approvedRider();
        $bike = $this->aBike(200000);
        Sanctum::actingAs($rider);

        // 1) Create booking (pending + hold + gateway order)
        $create = $this->withHeaders(['Idempotency-Key' => 'book-1'])
            ->postJson('/api/rental/v1/bookings', array_merge([
                'bike_id' => $bike->id, 'duration_type' => 'daily',
            ], $this->window(1, 2)))
            ->assertStatus(201)->json('data');

        $bookingId = $create['booking']['id'];
        $orderId = $create['payment']['order_id'];
        $paymentRef = $create['payment']['payment_reference'];
        $this->assertSame(296400, $create['booking']['amounts']['total']);
        $this->assertDatabaseHas('rental_bookings', ['id' => $bookingId, 'status' => 'pending']);

        // 2) Verify payment (signed) -> booking confirmed
        $paymentId = 'pay_TEST123';
        $this->withHeaders(['Idempotency-Key' => 'pay-1'])
            ->postJson('/api/rental/v1/payments/verify', [
                'payment_reference' => $paymentRef,
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $this->signature($orderId, $paymentId),
            ])->assertOk()->assertJsonPath('data.booking_status', 'confirmed');

        $this->assertDatabaseHas('rental_payments', ['booking_id' => $bookingId, 'status' => 'captured']);

        // 3) Unlock (start ride)
        $this->postJson("/api/rental/v1/bookings/{$bookingId}/unlock")
            ->assertOk()->assertJsonPath('data.status', 'active');

        // 4) Return (complete + auto deposit refund to wallet)
        $this->postJson("/api/rental/v1/bookings/{$bookingId}/return", [])
            ->assertOk()->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('rental_bookings', ['id' => $bookingId, 'status' => 'completed']);
        $this->assertDatabaseHas('rental_refunds', [
            'booking_id' => $bookingId, 'type' => 'deposit', 'amount' => 200000, 'status' => 'processed',
        ]);

        // Wallet credited with the deposit refund
        $this->assertSame(200000, (int) $rider->wallet()->first()->balance);
        $this->assertDatabaseHas('rental_wallet_transactions', [
            'user_id' => $rider->id, 'direction' => 'credit', 'amount' => 200000, 'source_type' => 'refund',
        ]);
    }

    public function test_cancellation_refunds_deposit_and_rental(): void
    {
        $rider = $this->approvedRider();
        $bike = $this->aBike(200000);
        Sanctum::actingAs($rider);

        $create = $this->withHeaders(['Idempotency-Key' => 'book-c'])
            ->postJson('/api/rental/v1/bookings', array_merge([
                'bike_id' => $bike->id, 'duration_type' => 'daily',
            ], $this->window(2, 2)))->json('data');

        $orderId = $create['payment']['order_id'];
        $ref = $create['payment']['payment_reference'];
        $this->withHeaders(['Idempotency-Key' => 'pay-c'])->postJson('/api/rental/v1/payments/verify', [
            'payment_reference' => $ref, 'razorpay_order_id' => $orderId,
            'razorpay_payment_id' => 'pay_C1', 'razorpay_signature' => $this->signature($orderId, 'pay_C1'),
        ])->assertOk();

        // Cancel well before start (>=24h) -> full rental refund + deposit
        $this->postJson("/api/rental/v1/bookings/{$create['booking']['id']}/cancel", ['reason' => 'Change of plans'])
            ->assertOk()->assertJsonPath('data.status', 'cancelled');

        // deposit 200000 + rental(base 80000 + tax 14400) fully refunded => wallet 294400
        $this->assertSame(294400, (int) $rider->wallet()->first()->balance);
    }
}
