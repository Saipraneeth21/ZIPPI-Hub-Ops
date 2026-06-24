<?php

namespace Tests\Feature\Hub;

use App\Models\Rental\Bike;
use App\Models\Rental\Booking;
use App\Models\Rental\Hub;
use App\Models\Rental\HubHandover;
use App\Models\Rental\HubStaff;
use App\Models\Rental\MaintenanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesRentalData;
use Tests\TestCase;

/**
 * Hub Operations API — auth, hub scoping, pickup handover, return, maintenance,
 * incidents and fleet. Verifies the additive layer reuses BookingService and
 * never lets a staff member touch another hub's data.
 */
class HubOperationsTest extends TestCase
{
    use CreatesRentalData, RefreshDatabase;

    private function staffForHub(Hub $hub, array $attrs = []): HubStaff
    {
        return HubStaff::factory()->create(array_merge([
            'hub_id' => $hub->id,
            'employee_code' => 'EMP'.fake()->unique()->numberBetween(1000, 9999),
            'password' => Hash::make('password'),
            'is_active' => true,
        ], $attrs));
    }

    private function bikeInHub(Hub $hub, string $status = 'available'): Bike
    {
        return Bike::factory()->create([
            'hub_id' => $hub->id,
            'city_id' => $hub->city_id,
            'status' => $status,
        ]);
    }

    private function booking(Hub $hub, Bike $bike, User $rider, string $status, ?Carbon $end = null): Booking
    {
        return Booking::create([
            'booking_code' => 'ZRP-'.fake()->unique()->numberBetween(10000, 99999),
            'user_id' => $rider->id,
            'bike_id' => $bike->id,
            'pickup_hub_id' => $hub->id,
            'return_hub_id' => $hub->id,
            'city_id' => $hub->city_id,
            'duration_type' => 'daily',
            'start_at' => now()->subDay(),
            'end_at' => $end ?? now()->addDay(),
            'deposit_amount' => 200000,
            'total_amount' => 296400,
            'status' => $status,
        ]);
    }

    public function test_login_issues_token_and_me_returns_hub(): void
    {
        $hub = Hub::factory()->create();
        $this->staffForHub($hub, ['employee_code' => 'HUBX1', 'name' => 'Asha']);

        $token = $this->postJson('/api/hub/v1/auth/login', [
            'employee_code' => 'HUBX1', 'password' => 'password',
        ])->assertOk()->assertJsonPath('data.staff.hub.id', $hub->id)->json('data.token');

        $this->assertNotEmpty($token);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/hub/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.employee_code', 'HUBX1');
    }

    public function test_login_rejects_bad_credentials_and_inactive_staff(): void
    {
        $hub = Hub::factory()->create();
        $this->staffForHub($hub, ['employee_code' => 'HUBX2']);
        $this->staffForHub($hub, ['employee_code' => 'HUBX3', 'is_active' => false]);

        $this->postJson('/api/hub/v1/auth/login', ['employee_code' => 'HUBX2', 'password' => 'wrong'])
            ->assertStatus(401);

        $this->postJson('/api/hub/v1/auth/login', ['employee_code' => 'HUBX3', 'password' => 'password'])
            ->assertStatus(403);
    }

    public function test_rider_token_cannot_access_hub_routes(): void
    {
        Sanctum::actingAs($this->approvedRider());

        $this->getJson('/api/hub/v1/dashboard')->assertStatus(403);
    }

    public function test_handovers_log_lists_records_with_status(): void
    {
        $hub = Hub::factory()->create();
        $staff = $this->staffForHub($hub);
        $bike = $this->bikeInHub($hub, 'booked');
        $booking = $this->booking($hub, $bike, $this->approvedRider(), 'active');
        HubHandover::create([
            'booking_id' => $booking->id,
            'hub_staff_id' => $staff->id,
            'battery_percent' => 90,
            'checklist' => ['bike_inspected' => true, 'helmet_issued' => true],
        ]);

        $this->actingAs($staff, 'hub')
            ->get('/hub/handovers')
            ->assertOk()
            ->assertSee('Handovers')
            ->assertSee($booking->booking_code)
            ->assertSee('On rent');
    }

    public function test_profile_page_renders_editable_form_for_staff(): void
    {
        $hub = Hub::factory()->create(['name' => 'Hitech City Hub']);
        $staff = $this->staffForHub($hub, ['employee_code' => 'HUBP1', 'role' => 'manager', 'name' => 'Asha Rao']);

        $this->actingAs($staff, 'hub')
            ->get('/hub/profile')
            ->assertOk()
            ->assertSee('Save changes')
            ->assertSee('Employee code')
            ->assertSee('Role')
            ->assertSee('Hub')
            ->assertSee('Asha Rao');
    }

    public function test_dashboard_counts_are_scoped_to_hub(): void
    {
        $hub = Hub::factory()->create();
        $staff = $this->staffForHub($hub);
        $rider = $this->approvedRider();

        $this->bikeInHub($hub, 'available');
        $this->bikeInHub($hub, 'available');
        $this->bikeInHub($hub, 'maintenance');
        $activeBike = $this->bikeInHub($hub, 'booked');

        $this->booking($hub, $activeBike, $rider, 'active', now()->endOfDay()); // due today
        $this->booking($hub, $this->bikeInHub($hub), $rider, 'confirmed');

        // Another hub's data must not leak in.
        $otherHub = Hub::factory()->create();
        $this->bikeInHub($otherHub, 'available');

        Sanctum::actingAs($staff);

        $this->getJson('/api/hub/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.cards.available_bikes', 3)
            ->assertJsonPath('data.cards.active_rentals', 1)
            ->assertJsonPath('data.cards.expected_returns_today', 1)
            ->assertJsonPath('data.cards.bikes_under_maintenance', 1)
            ->assertJsonPath('data.cards.maintenance_due', 1)
            ->assertJsonCount(1, 'data.upcoming_pickups')
            ->assertJsonCount(1, 'data.due_returns');
    }

    public function test_maintenance_due_counts_service_due_bikes_still_available(): void
    {
        $hub = Hub::factory()->create();
        $staff = $this->staffForHub($hub);

        // Available bike, but service due in 5 days -> should count as "due".
        $dueBike = $this->bikeInHub($hub, 'available');
        MaintenanceRecord::create([
            'bike_id' => $dueBike->id,
            'maintenance_type' => 'battery_service',
            'maintenance_date' => now()->subMonths(3)->toDateString(),
            'next_service_due' => now()->addDays(5)->toDateString(),
        ]);

        // Available bike with service far away -> should NOT count.
        $okBike = $this->bikeInHub($hub, 'available');
        MaintenanceRecord::create([
            'bike_id' => $okBike->id,
            'maintenance_type' => 'oil_change',
            'maintenance_date' => now()->toDateString(),
            'next_service_due' => now()->addMonths(2)->toDateString(),
        ]);

        Sanctum::actingAs($staff);

        $this->getJson('/api/hub/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.cards.bikes_under_maintenance', 0) // none pulled off road
            ->assertJsonPath('data.cards.maintenance_due', 1);        // the due-soon bike

        // Fleet "maintenance_due" filter returns the same due-soon bike.
        $this->getJson('/api/hub/v1/fleet?status=maintenance_due')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $dueBike->id);

        // And the hub "Maintenance Due" list page renders it.
        $this->actingAs($staff, 'hub')
            ->get('/hub/maintenance-due')
            ->assertOk()
            ->assertSee('Maintenance Due')
            ->assertSee($dueBike->registration_no);
    }

    public function test_handover_unlocks_booking_and_records_capture(): void
    {
        $hub = Hub::factory()->create();
        $staff = $this->staffForHub($hub);
        $bike = $this->bikeInHub($hub);
        $booking = $this->booking($hub, $bike, $this->approvedRider(), 'confirmed');

        Sanctum::actingAs($staff);

        $this->postJson("/api/hub/v1/bookings/{$booking->id}/handover", [
            'battery_percent' => 88,
            'checklist' => ['bike_inspected' => true, 'helmet_issued' => true],
        ])->assertOk()->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('rental_bookings', ['id' => $booking->id, 'status' => 'active']);
        $this->assertDatabaseHas('rental_bikes', ['id' => $bike->id, 'status' => 'booked']);
        $this->assertDatabaseHas('hub_handovers', [
            'booking_id' => $booking->id, 'hub_staff_id' => $staff->id, 'battery_percent' => 88,
        ]);
    }

    public function test_handover_rejects_non_confirmed_booking(): void
    {
        $hub = Hub::factory()->create();
        $staff = $this->staffForHub($hub);
        $booking = $this->booking($hub, $this->bikeInHub($hub), $this->approvedRider(), 'active');

        Sanctum::actingAs($staff);

        $this->postJson("/api/hub/v1/bookings/{$booking->id}/handover", [])->assertStatus(422);
        $this->assertDatabaseCount('hub_handovers', 0);
    }

    public function test_return_completes_booking_with_refund_and_capture(): void
    {
        $hub = Hub::factory()->create();
        $staff = $this->staffForHub($hub);
        $bike = $this->bikeInHub($hub, 'booked');
        $rider = $this->approvedRider();
        $booking = $this->booking($hub, $bike, $rider, 'active', now()->addDay());

        Sanctum::actingAs($staff);

        $this->postJson("/api/hub/v1/bookings/{$booking->id}/return", [
            'odometer_reading' => 1234,
            'battery_percent' => 40,
            'damage_notes' => 'Minor scratch',
        ])->assertOk()->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('rental_bookings', ['id' => $booking->id, 'status' => 'completed']);
        $this->assertDatabaseHas('rental_bikes', ['id' => $bike->id, 'status' => 'available']);
        $this->assertDatabaseHas('rental_refunds', [
            'booking_id' => $booking->id, 'type' => 'deposit', 'amount' => 200000, 'status' => 'processed',
        ]);
        $this->assertDatabaseHas('hub_returns', [
            'booking_id' => $booking->id, 'hub_staff_id' => $staff->id, 'odometer_reading' => 1234,
        ]);
    }

    public function test_staff_cannot_access_another_hubs_booking(): void
    {
        $hubA = Hub::factory()->create();
        $hubB = Hub::factory()->create();
        $staffA = $this->staffForHub($hubA);
        $bookingB = $this->booking($hubB, $this->bikeInHub($hubB), $this->approvedRider(), 'confirmed');

        Sanctum::actingAs($staffA);

        $this->getJson("/api/hub/v1/bookings/{$bookingB->id}")->assertStatus(404);
        $this->postJson("/api/hub/v1/bookings/{$bookingB->id}/handover", [])->assertStatus(404);
        $this->assertDatabaseHas('rental_bookings', ['id' => $bookingB->id, 'status' => 'confirmed']);
    }

    public function test_search_finds_only_own_hub_bookings(): void
    {
        $hubA = Hub::factory()->create();
        $hubB = Hub::factory()->create();
        $staffA = $this->staffForHub($hubA);
        $riderA = $this->approvedRider();
        $own = $this->booking($hubA, $this->bikeInHub($hubA), $riderA, 'confirmed');
        $this->booking($hubB, $this->bikeInHub($hubB), $this->approvedRider(), 'confirmed');

        Sanctum::actingAs($staffA);

        $this->getJson('/api/hub/v1/bookings/search?q='.$riderA->mobile)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.booking_id', $own->booking_code);
    }

    public function test_maintenance_report_creates_record(): void
    {
        $hub = Hub::factory()->create();
        $staff = $this->staffForHub($hub);
        $bike = $this->bikeInHub($hub);

        Sanctum::actingAs($staff);

        $this->postJson('/api/hub/v1/maintenance', [
            'bike_id' => $bike->id,
            'category' => 'Brake',
            'description' => 'Front brake spongy',
        ])->assertStatus(201)->assertJsonPath('data.status', 'open');

        $this->assertDatabaseHas('rental_maintenance_records', [
            'bike_id' => $bike->id, 'maintenance_type' => 'Brake', 'status' => 'open',
            'reported_by_hub_staff_id' => $staff->id,
        ]);
    }

    public function test_maintenance_rejects_bike_from_other_hub(): void
    {
        $hub = Hub::factory()->create();
        $staff = $this->staffForHub($hub);
        $otherBike = $this->bikeInHub(Hub::factory()->create());

        Sanctum::actingAs($staff);

        $this->postJson('/api/hub/v1/maintenance', [
            'bike_id' => $otherBike->id, 'category' => 'Tyre',
        ])->assertStatus(404);
    }

    public function test_incident_report_creates_record_with_booking_link(): void
    {
        $hub = Hub::factory()->create();
        $staff = $this->staffForHub($hub);
        $bike = $this->bikeInHub($hub);
        $booking = $this->booking($hub, $bike, $this->approvedRider(), 'active');

        Sanctum::actingAs($staff);

        $this->postJson('/api/hub/v1/incidents', [
            'bike_id' => $bike->id,
            'booking_id' => $booking->id,
            'incident_type' => 'Breakdown',
            'severity' => 'moderate',
            'description' => 'Motor cut out mid-ride',
        ])->assertStatus(201)->assertJsonPath('data.incident_type', 'Breakdown');

        $this->assertDatabaseHas('rental_incident_reports', [
            'bike_id' => $bike->id, 'booking_id' => $booking->id, 'incident_type' => 'Breakdown',
            'reported_by_hub_staff_id' => $staff->id,
        ]);
    }

    public function test_fleet_lists_and_filters_hub_bikes(): void
    {
        $hub = Hub::factory()->create();
        $staff = $this->staffForHub($hub);
        $this->bikeInHub($hub, 'available');
        $this->bikeInHub($hub, 'maintenance');
        $this->bikeInHub(Hub::factory()->create(), 'available'); // other hub

        Sanctum::actingAs($staff);

        $this->getJson('/api/hub/v1/fleet')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/hub/v1/fleet?status=available')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/hub/v1/fleet?status=maintenance')->assertOk()->assertJsonCount(1, 'data');
    }
}
