<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesRentalData;
use Tests\TestCase;

class KycGateTest extends TestCase
{
    use RefreshDatabase, CreatesRentalData;

    public function test_booking_blocked_without_approved_kyc(): void
    {
        $rider = User::factory()->create(); // kyc_status = none
        $bike = $this->aBike();
        Sanctum::actingAs($rider);

        $this->withHeaders(['Idempotency-Key' => 'k1'])
            ->postJson('/api/rental/v1/bookings', array_merge([
                'bike_id' => $bike->id, 'duration_type' => 'daily',
            ], $this->window()))
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_booking_allowed_after_kyc_approved(): void
    {
        $rider = $this->approvedRider();
        $bike = $this->aBike();
        Sanctum::actingAs($rider);

        $this->withHeaders(['Idempotency-Key' => 'k2'])
            ->postJson('/api/rental/v1/bookings', array_merge([
                'bike_id' => $bike->id, 'duration_type' => 'daily',
            ], $this->window()))
            ->assertStatus(201)
            ->assertJsonPath('data.booking.status', 'pending');
    }

    public function test_booking_requires_idempotency_key(): void
    {
        $rider = $this->approvedRider();
        $bike = $this->aBike();
        Sanctum::actingAs($rider);

        $this->postJson('/api/rental/v1/bookings', array_merge([
            'bike_id' => $bike->id, 'duration_type' => 'daily',
        ], $this->window()))->assertStatus(422);
    }
}
