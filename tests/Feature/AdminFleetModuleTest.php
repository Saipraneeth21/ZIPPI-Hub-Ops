<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Filament\Resources\BikeCategories\Pages\CreateBikeCategory;
use App\Filament\Resources\Bikes\Pages\ListBikes;
use App\Filament\Resources\Bikes\RelationManagers\PricingRelationManager;
use App\Filament\Resources\Bikes\Pages\EditBike;
use App\Models\Rental\AdminUser;
use App\Models\Rental\Booking;
use App\Models\Rental\Bike;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesRentalData;
use Tests\TestCase;

class AdminFleetModuleTest extends TestCase
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

    public function test_ops_can_view_bikes_list(): void
    {
        $this->actingAs($this->admin(AdminRole::Ops), 'admin');
        $bike = $this->aBike();

        Livewire::test(ListBikes::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$bike]);
    }

    public function test_support_cannot_view_bikes(): void
    {
        $this->actingAs($this->admin(AdminRole::Support), 'admin');

        Livewire::test(ListBikes::class)->assertForbidden();
    }

    public function test_category_deposit_is_stored_in_paise(): void
    {
        $this->actingAs($this->admin(AdminRole::Ops), 'admin');

        Livewire::test(CreateBikeCategory::class)
            ->fillForm([
                'name' => 'Electric',
                'slug' => 'electric',
                'default_deposit_amount' => 2500, // ₹2,500 entered
                'sort_order' => 0,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('rental_bike_categories', [
            'slug' => 'electric',
            'default_deposit_amount' => 250000, // stored as paise
        ]);
    }

    public function test_set_status_action_updates_availability(): void
    {
        $this->actingAs($this->admin(AdminRole::Ops), 'admin');
        $bike = $this->aBike();

        Livewire::test(ListBikes::class)
            ->callTableAction('setStatus', $bike, data: ['status' => 'maintenance']);

        $this->assertSame('maintenance', $bike->fresh()->status);
    }

    public function test_delete_is_blocked_when_active_bookings_exist(): void
    {
        $ops = $this->admin(AdminRole::Ops);
        $this->actingAs($ops, 'admin');

        $bike = $this->aBike();
        $rider = $this->approvedRider();
        Booking::create([
            'booking_code' => 'ZBTEST0001',
            'user_id' => $rider->id,
            'bike_id' => $bike->id,
            'pickup_hub_id' => $bike->hub_id,
            'return_hub_id' => $bike->hub_id,
            'city_id' => $bike->city_id,
            'duration_type' => 'daily',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(2),
            'total_amount' => 50000,
            'status' => 'active',
        ]);

        $this->assertTrue($bike->hasBlockingBookings());
        $this->assertFalse(\App\Filament\Resources\Bikes\BikeResource::canDelete($bike->fresh()));

        // Delete stays visible (so staff see the option) but is disabled with a
        // tooltip explaining why, rather than being hidden outright.
        Livewire::test(ListBikes::class)
            ->assertTableActionVisible('delete', $bike)
            ->assertTableActionDisabled('delete', $bike);
    }

    public function test_pricing_relation_manager_stores_paise(): void
    {
        $this->actingAs($this->admin(AdminRole::Ops), 'admin');
        $bike = $this->aBike();

        Livewire::test(PricingRelationManager::class, [
            'ownerRecord' => $bike,
            'pageClass' => EditBike::class,
        ])
            ->callTableAction('create', data: [
                'hourly_rate' => 30,   // ₹30/hr
                'daily_rate' => 400,   // ₹400/day
                'monthly_rate' => 7000,
                'min_hours' => 2,
                'is_active' => true,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('rental_bike_pricing', [
            'bike_id' => $bike->id,
            'hourly_rate' => 3000,
            'daily_rate' => 40000,
            'monthly_rate' => 700000,
            'min_hours' => 2,
        ]);
    }
}
