<?php
namespace Database\Factories\Rental;

use App\Models\Rental\City;
use Illuminate\Database\Eloquent\Factories\Factory;

class CityFactory extends Factory
{
    protected $model = City::class;
    public function definition(): array
    {
        return ['name' => 'Hyderabad', 'state' => 'TS', 'country' => 'IN', 'timezone' => 'Asia/Kolkata', 'is_active' => true];
    }
}
