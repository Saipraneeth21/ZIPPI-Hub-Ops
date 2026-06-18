<?php
namespace Tests\Feature;

use App\Models\Rental\Coupon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesRentalData;
use Tests\TestCase;

class QuoteTest extends TestCase
{
    use RefreshDatabase, CreatesRentalData;

    public function test_daily_quote_breakdown_without_coupon(): void
    {
        $rider = $this->approvedRider();
        $bike = $this->aBike(200000);
        Sanctum::actingAs($rider);

        $resp = $this->postJson('/api/rental/v1/bookings/quote', array_merge([
            'bike_id' => $bike->id, 'duration_type' => 'daily',
        ], $this->window(1, 2)))->assertOk()->json('data');

        // 2 days * ₹400 = 80000 base; tax 18% = 14400; fee 2000; deposit 200000
        $this->assertEquals(2, $resp['computed_units']);
        $this->assertSame(80000, $resp['base_amount']);
        $this->assertSame(14400, $resp['tax_amount']);
        $this->assertSame(2000, $resp['platform_fee']);
        $this->assertSame(200000, $resp['deposit_amount']);
        $this->assertSame(296400, $resp['total_amount']);
    }

    public function test_daily_quote_with_flat_coupon(): void
    {
        $rider = $this->approvedRider();
        $bike = $this->aBike(200000);
        Coupon::factory()->create(['code' => 'FLAT100', 'type' => 'flat', 'value' => 10000, 'min_booking_amount' => 0]);
        Sanctum::actingAs($rider);

        $resp = $this->postJson('/api/rental/v1/bookings/quote', array_merge([
            'bike_id' => $bike->id, 'duration_type' => 'daily', 'coupon_code' => 'FLAT100',
        ], $this->window(1, 2)))->assertOk()->json('data');

        // base 80000 - 10000 = 70000; tax 18% of 70000 = 12600; +fee 2000 +deposit 200000 = 284600
        $this->assertSame(10000, $resp['discount_amount']);
        $this->assertSame(12600, $resp['tax_amount']);
        $this->assertSame(284600, $resp['total_amount']);
        $this->assertSame('FLAT100', $resp['coupon_code']);
    }

    public function test_hourly_quote_respects_min_hours(): void
    {
        $rider = $this->approvedRider();
        $bike = $this->aBike();
        Sanctum::actingAs($rider);

        // 1-hour window but min_hours = 2 -> billed 2 hours * ₹30 = 6000
        $resp = $this->postJson('/api/rental/v1/bookings/quote', [
            'bike_id' => $bike->id, 'duration_type' => 'hourly',
            'start_at' => now()->addDay()->startOfHour()->toIso8601String(),
            'end_at' => now()->addDay()->startOfHour()->addHour()->toIso8601String(),
        ])->assertOk()->json('data');

        $this->assertEquals(2, $resp['computed_units']);
        $this->assertSame(6000, $resp['base_amount']);
    }
}
