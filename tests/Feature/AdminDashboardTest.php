<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\BookingStatus;
use App\Filament\Widgets\ActiveRidesTable;
use App\Filament\Widgets\BookingsByStatusStats;
use App\Filament\Widgets\OverviewStats;
use App\Filament\Widgets\RevenueTrendChart;
use App\Models\Rental\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesRentalData;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use CreatesRentalData;
    use RefreshDatabase;

    private function admin(AdminRole $role = AdminRole::SuperAdmin): AdminUser
    {
        return AdminUser::create([
            'name' => 'T Admin',
            'email' => strtolower($role->value) . '@zippi.in',
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    /** @return array<string, ?string> label => url */
    private function statUrls(): array
    {
        $stats = (new \ReflectionMethod(OverviewStats::class, 'getStats'))->invoke(new OverviewStats());
        $out = [];
        foreach ($stats as $stat) {
            $label = (new \ReflectionObject($stat))->getProperty('label')->getValue($stat);
            $out[$label] = $stat->getUrl();
        }

        return $out;
    }

    public function test_kpi_cards_link_to_their_sections_for_super_admin(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $urls = $this->statUrls();

        $this->assertStringContainsString('/admin/payments', $urls['Revenue (30d)']);
        $this->assertStringContainsString('/admin/bookings?activeTab=active', $urls['Active Rentals']);
        $this->assertStringContainsString('/admin/bookings?activeTab=completed', $urls['Completed (30d)']);
        $this->assertStringContainsString('/admin/bikes', $urls['Fleet Utilization']);
        $this->assertStringContainsString('/admin/kyc', $urls['KYC Approval Rate']);
        $this->assertStringContainsString('/admin/users', $urls['New Users (30d)']);
    }

    public function test_kpi_card_links_are_gated_by_permission(): void
    {
        // kyc_reviewer can't view payments/orders/bikes/users — those cards aren't links.
        $this->actingAs($this->admin(AdminRole::KycReviewer), 'admin');
        $urls = $this->statUrls();

        $this->assertNull($urls['Revenue (30d)']);
        $this->assertNull($urls['Active Rentals']);
        $this->assertNull($urls['Fleet Utilization']);
        // ...but KYC is their domain, so that card still links.
        $this->assertStringContainsString('/admin/kyc', $urls['KYC Approval Rate']);
    }

    public function test_dashboard_widgets_render_for_authenticated_admin(): void
    {
        $this->actingAs($this->admin(), 'admin');

        Livewire::test(OverviewStats::class)->assertOk();
        Livewire::test(RevenueTrendChart::class)->assertOk();
        Livewire::test(BookingsByStatusStats::class)->assertOk();
        Livewire::test(ActiveRidesTable::class)->assertOk();
    }

    public function test_active_rides_widget_lists_only_active_bookings(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $rider = $this->approvedRider();
        $bike = $this->aBike();
        $active = \App\Models\Rental\Booking::create([
            'booking_code' => 'BK-ACTIVE', 'user_id' => $rider->id, 'bike_id' => $bike->id,
            'pickup_hub_id' => $bike->hub_id, 'return_hub_id' => $bike->hub_id, 'city_id' => $bike->city_id,
            'duration_type' => 'hourly', 'status' => BookingStatus::Active->value,
            'start_at' => now()->subHour(), 'end_at' => now()->addHour(), 'total_amount' => 10000,
        ]);
        $completed = \App\Models\Rental\Booking::create([
            'booking_code' => 'BK-DONE', 'user_id' => $rider->id, 'bike_id' => $bike->id,
            'pickup_hub_id' => $bike->hub_id, 'return_hub_id' => $bike->hub_id, 'city_id' => $bike->city_id,
            'duration_type' => 'hourly', 'status' => BookingStatus::Completed->value,
            'start_at' => now()->subDay(), 'end_at' => now()->subDay(), 'total_amount' => 10000,
        ]);

        Livewire::test(ActiveRidesTable::class)
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$completed]);
    }

    public function test_active_rides_widget_hidden_without_orders_permission(): void
    {
        $this->actingAs($this->admin(AdminRole::KycReviewer), 'admin');

        $this->assertFalse(ActiveRidesTable::canView());
    }

    public function test_breadcrumbs_start_with_a_dashboard_root(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->get(\App\Filament\Resources\Payments\PaymentResource::getUrl('index'))
            ->assertOk()
            ->assertSeeInOrder(['fi-breadcrumbs-list', 'Dashboard', 'Payments', 'List'])
            ->assertSee(filament()->getPanel('admin')->getUrl(), false);
    }

    public function test_inactive_admin_cannot_access_panel(): void
    {
        $admin = $this->admin();
        $admin->update(['is_active' => false]);

        $panel = filament()->getPanel('admin');
        $this->assertFalse($admin->canAccessPanel($panel));
    }

    public function test_rbac_gates_follow_the_matrix(): void
    {
        $support = $this->admin(AdminRole::Support);
        $this->assertTrue($support->can('orders.view'));
        $this->assertFalse($support->can('wallet.adjust'));
        $this->assertFalse($support->can('kyc.review'));

        $kyc = $this->admin(AdminRole::KycReviewer);
        $this->assertTrue($kyc->can('kyc.review'));
        $this->assertFalse($kyc->can('bikes.manage'));

        // super_admin is allowed everything via the Gate::before hook.
        $this->assertTrue($this->admin()->can('wallet.adjust'));
    }
}
