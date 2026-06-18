<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesRentalData;
use Tests\TestCase;

class DoubleBookingTest extends TestCase
{
    use RefreshDatabase, CreatesRentalData;

    public function test_overlapping_booking_on_same_bike_is_rejected(): void
    {
        $bike = $this->aBike();

        $rider1 = $this->approvedRider();
        Sanctum::actingAs($rider1);
        $this->withHeaders(['Idempotency-Key' => 'a'])
            ->postJson('/api/rental/v1/bookings', array_merge([
                'bike_id' => $bike->id, 'duration_type' => 'daily',
            ], $this->window(1, 2)))->assertStatus(201);

        // Second rider tries an overlapping window on the same bike.
        $rider2 = $this->approvedRider();
        Sanctum::actingAs($rider2);
        $this->withHeaders(['Idempotency-Key' => 'b'])
            ->postJson('/api/rental/v1/bookings', array_merge([
                'bike_id' => $bike->id, 'duration_type' => 'daily',
            ], $this->window(2, 2)))   // overlaps day 2-3 with first booking (day 1-3)
            ->assertStatus(409);
    }

    public function test_non_overlapping_booking_is_allowed(): void
    {
        $bike = $this->aBike();
        $rider = $this->approvedRider();
        Sanctum::actingAs($rider);

        $this->withHeaders(['Idempotency-Key' => 'x'])
            ->postJson('/api/rental/v1/bookings', array_merge([
                'bike_id' => $bike->id, 'duration_type' => 'daily',
            ], $this->window(1, 1)))->assertStatus(201);

        // Starts after the first ends -> allowed.
        $this->withHeaders(['Idempotency-Key' => 'y'])
            ->postJson('/api/rental/v1/bookings', array_merge([
                'bike_id' => $bike->id, 'duration_type' => 'daily',
            ], $this->window(5, 1)))->assertStatus(201);
    }
}
