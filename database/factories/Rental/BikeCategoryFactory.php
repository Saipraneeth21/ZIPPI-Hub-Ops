<?php
namespace Database\Factories\Rental;

use App\Models\Rental\BikeCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BikeCategoryFactory extends Factory
{
    protected $model = BikeCategory::class;
    public function definition(): array
    {
        $name = fake()->randomElement(['Scooter', 'Geared', 'Electric']);
        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->numberBetween(1, 99999),
            'default_deposit_amount' => 200000,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
