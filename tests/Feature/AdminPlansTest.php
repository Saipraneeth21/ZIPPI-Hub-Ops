<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Filament\Resources\BikePricings\Pages\CreateBikePricing;
use App\Filament\Resources\BikePricings\Pages\ListBikePricings;
use App\Models\Rental\AdminUser;
use App\Models\Rental\BikePricing;
use App\Services\Rental\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesRentalData;
use Tests\TestCase;
use Livewire\Livewire;

class AdminPlansTest extends TestCase
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

    public function test_ops_can_create_a_plan_with_a_weekly_rate(): void
    {
        $this->actingAs($this->admin(AdminRole::Ops), 'admin');
        $bike = $this->aBike();
        // aBike() ships with default pricing; remove it so the create form has a clean bike.
        BikePricing::where('bike_id', $bike->id)->delete();

        Livewire::test(CreateBikePricing::class)
            ->fillForm([
                'bike_id' => $bike->id,
                'min_hours' => 2,
                'hourly_rate' => 30,
                'daily_rate' => 400,
                'weekly_rate' => 2600,   // ₹2,600 / week
                'monthly_rate' => 9000,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('rental_bike_pricing', [
            'bike_id' => $bike->id,
            'weekly_rate' => 260000, // paise
            'monthly_rate' => 900000,
        ]);
    }

    public function test_plans_list_renders_for_ops(): void
    {
        $this->actingAs($this->admin(AdminRole::Ops), 'admin');
        $bike = $this->aBike();
        $plan = BikePricing::where('bike_id', $bike->id)->first();

        Livewire::test(ListBikePricings::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$plan]);
    }

    public function test_support_cannot_view_plans(): void
    {
        $this->actingAs($this->admin(AdminRole::Support), 'admin');

        Livewire::test(ListBikePricings::class)->assertForbidden();
    }

    public function test_pricing_service_quotes_a_weekly_rental(): void
    {
        $bike = $this->aBike();
        BikePricing::where('bike_id', $bike->id)->update([
            'weekly_rate' => 260000, // ₹2,600 / week
            'is_active' => true,
        ]);
        $bike->refresh();

        $start = \Illuminate\Support\Carbon::parse('2026-07-01 00:00:00');
        $quote = app(PricingService::class)->quote(
            $bike,
            'weekly',
            $start,
            $start->copy()->addDays(7), // exactly one week (168h)
        );

        $this->assertSame('weekly', $quote->durationType);
        $this->assertSame(1.0, $quote->computedUnits);
        $this->assertSame(260000, $quote->baseAmount->paise); // one week at the weekly rate
    }
}
