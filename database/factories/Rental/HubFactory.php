<?php
namespace Database\Factories\Rental;

use App\Models\Rental\Hub;
use Illuminate\Database\Eloquent\Factories\Factory;

class HubFactory extends Factory
{
    protected $model = Hub::class;
    public function definition(): array
    {
        return [
            'city_id' => \App\Models\Rental\City::factory(),
            'name' => fake()->company() . ' Hub',
            'address' => fake()->address(),
            'latitude' => 17.4435, 'longitude' => 78.3772,
            'is_active' => true,
        ];
    }
}
