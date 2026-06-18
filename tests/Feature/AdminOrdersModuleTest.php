<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Filament\Resources\Bookings\Pages\ViewBooking;
use App\Models\Rental\AdminUser;
use App\Models\Rental\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Concerns\CreatesRentalData;
use Tests\TestCase;

class AdminOrdersModuleTest extends TestCase
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

    private function booking(string $status, array $overrides = []): Booking
    {
        $bike = $this->aBike();
        $rider = $this->approvedRider();

        return Booking::create(array_merge([
            'booking_code' => 'ZB' . strtoupper(Str::random(8)),
            'user_id' => $rider->id,
            'bike_id' => $bike->id,
            'pickup_hub_id' => $bike->hub_id,
            'return_hub_id' => $bike->hub_id,
            'city_id' => $bike->city_id,
            'duration_type' => 'daily',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(2),
            'base_amount' => 40000,
            'tax_amount' => 7200,
            'deposit_amount' => 200000,
            'total_amount' => 247200,
            'status' => $status,
        ], $overrides));
    }

    public function test_orders_list_renders_with_lifecycle_tabs(): void
    {
        $this->actingAs($this->admin(AdminRole::Support), 'admin');
        $upcoming = $this->booking('confirmed');
        $active = $this->booking('active');

        Livewire::test(ListBookings::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$upcoming, $active])
            // Switch to the Active lifecycle tab (real Livewire update path).
            ->set('activeTab', 'active')
            ->assertOk()
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$upcoming])
            ->set('activeTab', 'upcoming')
            ->assertOk()
            ->assertCanSeeTableRecords([$upcoming])
            ->assertCanNotSeeTableRecords([$active]);
    }

    public function test_kyc_reviewer_cannot_view_orders(): void
    {
        $this->actingAs($this->admin(AdminRole::KycReviewer), 'admin');

        Livewire::test(ListBookings::class)->assertForbidden();
    }

    public function test_ops_can_cancel_an_upcoming_booking(): void
    {
        $this->actingAs($this->admin(AdminRole::Ops), 'admin');
        $booking = $this->booking('confirmed');

        Livewire::test(ViewBooking::class, ['record' => $booking->getKey()])
            ->callAction('cancelBooking', data: ['reason' => 'Rider request', 'override_policy' => true]);

        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertDatabaseHas('rental_booking_status_history', [
            'booking_id' => $booking->id,
            'to_status' => 'cancelled',
        ]);
    }

    public function test_ops_can_force_return_an_active_rental(): void
    {
        $this->actingAs($this->admin(AdminRole::Ops), 'admin');
        $booking = $this->booking('active');

        Livewire::test(ViewBooking::class, ['record' => $booking->getKey()])
            ->callAction('forceReturn', data: ['return_hub_id' => $booking->return_hub_id]);

        $fresh = $booking->fresh();
        $this->assertSame('completed', $fresh->status);
        $this->assertNotNull($fresh->actual_end_at);
        // Deposit auto-refunded to wallet.
        $this->assertDatabaseHas('rental_refunds', [
            'booking_id' => $booking->id,
            'type' => 'deposit',
        ]);
    }

    public function test_apply_deductions_records_a_partial_refund(): void
    {
        $this->actingAs($this->admin(AdminRole::Ops), 'admin');
        $booking = $this->booking('completed');

        Livewire::test(ViewBooking::class, ['record' => $booking->getKey()])
            ->callAction('applyDeductions', data: [
                'amount' => 500,       // ₹500 gross
                'deductions' => 100,   // ₹100 deducted
                'note' => 'Helmet damage',
                'destination' => 'wallet',
            ]);

        $this->assertDatabaseHas('rental_refunds', [
            'booking_id' => $booking->id,
            'type' => 'partial',
            'amount' => 40000,     // net ₹400 in paise
            'deductions' => 10000, // ₹100 in paise
        ]);
    }

    public function test_support_cannot_use_management_actions(): void
    {
        $this->actingAs($this->admin(AdminRole::Support), 'admin');
        $booking = $this->booking('confirmed');

        Livewire::test(ViewBooking::class, ['record' => $booking->getKey()])
            ->assertActionHidden('cancelBooking')
            ->assertActionHidden('applyDeductions');
    }
}
