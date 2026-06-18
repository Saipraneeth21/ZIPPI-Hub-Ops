<?php
namespace Database\Factories\Rental;

use App\Models\Rental\BikePricing;
use Illuminate\Database\Eloquent\Factories\Factory;

class BikePricingFactory extends Factory
{
    protected $model = BikePricing::class;
    public function definition(): array
    {
        return [
            'hourly_rate' => 3000,    // ₹30/hr
            'daily_rate' => 40000,    // ₹400/day
            'monthly_rate' => 700000, // ₹7000/month
            'min_hours' => 2,
            'is_active' => true,
        ];
    }
}
