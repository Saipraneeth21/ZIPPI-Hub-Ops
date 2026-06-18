<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Filament\Pages\PushCampaign;
use App\Filament\Resources\Coupons\Pages\CreateCoupon;
use App\Filament\Resources\Coupons\Pages\ListCoupons;
use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Models\Rental\AdminUser;
use App\Models\Rental\Booking;
use App\Models\Rental\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Concerns\CreatesRentalData;
use Tests\TestCase;

class AdminSettingsMarketingTest extends TestCase
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

    private function aReview(string $status = 'published', int $rating = 5): Review
    {
        $bike = $this->aBike();
        $rider = $this->approvedRider();
        $booking = Booking::create([
            'booking_code' => 'ZB' . strtoupper(Str::random(8)),
            'user_id' => $rider->id, 'bike_id' => $bike->id,
            'pickup_hub_id' => $bike->hub_id, 'return_hub_id' => $bike->hub_id, 'city_id' => $bike->city_id,
            'duration_type' => 'daily', 'start_at' => now()->subDays(2), 'end_at' => now()->subDay(),
            'total_amount' => 50000, 'status' => 'completed',
        ]);

        return Review::create([
            'booking_id' => $booking->id, 'user_id' => $rider->id, 'bike_id' => $bike->id,
            'rating' => $rating, 'comment' => 'Nice ride', 'status' => $status,
        ]);
    }

    // --- Marketing: Coupons ---

    public function test_ops_can_create_a_flat_coupon_stored_in_paise(): void
    {
        $this->actingAs($this->admin(AdminRole::Ops), 'admin');

        Livewire::test(CreateCoupon::class)
            ->fillForm([
                'code' => 'zippi50', 'type' => 'flat', 'value' => 50,
                'min_booking_amount' => 200, 'usage_limit_per_user' => 1,
                'valid_from' => now(), 'valid_to' => now()->addMonth(), 'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('rental_coupons', [
            'code' => 'ZIPPI50',       // upper-cased
            'value' => 5000,           // ₹50 in paise
            'min_booking_amount' => 20000,
        ]);
    }

    public function test_support_cannot_view_coupons(): void
    {
        $this->actingAs($this->admin(AdminRole::Support), 'admin');

        Livewire::test(ListCoupons::class)->assertForbidden();
    }

    // --- Marketing: Push ---

    public function test_push_campaign_sends_to_audience(): void
    {
        $this->actingAs($this->admin(AdminRole::Ops), 'admin');
        User::factory()->count(3)->create();

        Livewire::test(PushCampaign::class)
            ->fillForm(['title' => 'Weekend offer', 'body' => '20% off', 'audience' => 'all'])
            ->callAction('send');

        $this->assertDatabaseCount('rental_notifications', 3);
    }

    public function test_support_cannot_access_push_campaign(): void
    {
        $this->actingAs($this->admin(AdminRole::Support), 'admin');

        Livewire::test(PushCampaign::class)->assertForbidden();
    }

    // --- Reviews ---

    public function test_ops_can_hide_a_published_review(): void
    {
        $this->actingAs($this->admin(AdminRole::Ops), 'admin');
        $review = $this->aReview('published');

        Livewire::test(ListReviews::class)
            ->callTableAction('moderate', $review, data: ['note' => 'Offensive language']);

        $this->assertSame('hidden', $review->fresh()->status);
    }

    public function test_kyc_reviewer_cannot_view_reviews(): void
    {
        $this->actingAs($this->admin(AdminRole::KycReviewer), 'admin');

        Livewire::test(ListReviews::class)->assertForbidden();
    }
}
