<?php
namespace Database\Factories\Rental;

use App\Models\Rental\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

class CouponFactory extends Factory
{
    protected $model = Coupon::class;
    public function definition(): array
    {
        return [
            'code' => 'ZIPPI' . fake()->unique()->numberBetween(1, 99999),
            'type' => 'flat',
            'value' => 10000, // ₹100 off
            'min_booking_amount' => 0,
            'usage_limit_per_user' => 1,
            'usage_limit_total' => 1000,
            'used_count' => 0,
            'valid_from' => now()->subDay(),
            'valid_to' => now()->addMonth(),
            'is_active' => true,
        ];
    }
}
