<?php
namespace Database\Factories\Rental;

use App\Models\Rental\Bike;
use App\Models\Rental\BikeCategory;
use App\Models\Rental\City;
use App\Models\Rental\Hub;
use Illuminate\Database\Eloquent\Factories\Factory;

class BikeFactory extends Factory
{
    protected $model = Bike::class;
    public function definition(): array
    {
        return [
            'category_id' => BikeCategory::factory(),
            'hub_id' => Hub::factory(),
            'city_id' => City::factory(),
            'name' => fake()->randomElement(['Activa 6G', 'Jupiter', 'Ola S1']),
            'registration_no' => 'TS' . fake()->unique()->numerify('##AB####'),
            'iot_device_id' => 'IOT-' . fake()->unique()->numerify('#####'),
            'fuel_type' => 'petrol',
            'year' => 2024,
            'specifications' => ['engine_cc' => 110, 'mileage_kmpl' => 45, 'seats' => 2],
            'status' => 'available',
            'security_deposit_amount' => 200000,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Bike $bike) {
            \App\Models\Rental\BikePricing::factory()->create(['bike_id' => $bike->id]);
        });
    }
}
