<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Filament\Pages\LiveMap;
use App\Filament\Resources\GeofenceAlerts\Pages\ListGeofenceAlerts;
use App\Models\Rental\AdminUser;
use App\Models\Rental\Bike;
use App\Models\Rental\BikeTelemetry;
use App\Models\Rental\Booking;
use App\Models\Rental\GeofenceAlert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Concerns\CreatesRentalData;
use Tests\TestCase;

class AdminTrackingModuleTest extends TestCase
{
    use RefreshDatabase, CreatesRentalData;

    private function admin(AdminRole $role): AdminUser
    {
        return AdminUser::create([
            'name' => 'T Admin',
            'email' => strtolower($role->value) . '@zippi.in',
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function activeRentalWithTelemetry(): Booking
    {
        $bike = $this->aBike();
        $rider = $this->approvedRider();
        $booking = Booking::create([
            'booking_code' => 'ZB' . strtoupper(Str::random(8)),
            'user_id' => $rider->id, 'bike_id' => $bike->id,
            'pickup_hub_id' => $bike->hub_id, 'return_hub_id' => $bike->hub_id, 'city_id' => $bike->city_id,
            'duration_type' => 'daily', 'start_at' => now()->subHour(), 'end_at' => now()->addHours(3),
            'total_amount' => 50000, 'status' => 'active',
        ]);

        foreach (range(1, 3) as $n) {
            BikeTelemetry::create([
                'bike_id' => $bike->id, 'booking_id' => $booking->id,
                'latitude' => 12.97 + $n / 1000, 'longitude' => 77.59 + $n / 1000,
                'speed_kmph' => 20, 'battery_pct' => 80, 'ignition' => true,
                'recorded_at' => now()->subMinutes(10 - $n),
            ]);
        }

        return $booking;
    }

    public function test_positions_feed_returns_active_rentals_for_authorized_admin(): void
    {
        $admin = $this->admin(AdminRole::Ops);
        $booking = $this->activeRentalWithTelemetry();

        $this->actingAs($admin, 'admin')
            ->getJson(route('admin.tracking.positions'))
            ->assertOk()
            ->assertJsonPath('counts.active', 1)
            ->assertJsonPath('rentals.0.booking_code', $booking->booking_code)
            ->assertJsonCount(3, 'rentals.0.trail');
    }

    public function test_positions_feed_includes_open_alerts(): void
    {
        $admin = $this->admin(AdminRole::Ops);
        $booking = $this->activeRentalWithTelemetry();
        GeofenceAlert::create([
            'bike_id' => $booking->bike_id, 'booking_id' => $booking->id,
            'latitude' => 12.98, 'longitude' => 77.60, 'severity' => 'high', 'resolved' => false,
        ]);

        $this->actingAs($admin, 'admin')
            ->getJson(route('admin.tracking.positions'))
            ->assertOk()
            ->assertJsonPath('counts.open_alerts', 1)
            ->assertJsonPath('alerts.0.severity', 'high');
    }

    public function test_kyc_reviewer_is_forbidden_from_positions_feed(): void
    {
        $this->actingAs($this->admin(AdminRole::KycReviewer), 'admin')
            ->getJson(route('admin.tracking.positions'))
            ->assertForbidden();
    }

    public function test_live_map_access_follows_tracking_view(): void
    {
        $this->actingAs($this->admin(AdminRole::Support), 'admin');
        Livewire::test(LiveMap::class)->assertOk(); // support has tracking.view

        $this->actingAs($this->admin(AdminRole::KycReviewer), 'admin');
        Livewire::test(LiveMap::class)->assertForbidden();
    }

    public function test_ops_can_resolve_a_geofence_alert(): void
    {
        $this->actingAs($this->admin(AdminRole::Ops), 'admin');
        $bike = Bike::factory()->create();
        $alert = GeofenceAlert::create([
            'bike_id' => $bike->id, 'latitude' => 12.9, 'longitude' => 77.5,
            'severity' => 'medium', 'resolved' => false,
        ]);

        Livewire::test(ListGeofenceAlerts::class)
            ->callTableAction('resolve', $alert);

        $this->assertTrue($alert->fresh()->resolved);
    }

    public function test_support_cannot_resolve_alerts(): void
    {
        $this->actingAs($this->admin(AdminRole::Support), 'admin');
        $bike = Bike::factory()->create();
        $alert = GeofenceAlert::create([
            'bike_id' => $bike->id, 'latitude' => 12.9, 'longitude' => 77.5,
            'severity' => 'low', 'resolved' => false,
        ]);

        Livewire::test(ListGeofenceAlerts::class)
            ->assertTableActionHidden('resolve', $alert);
    }
}
