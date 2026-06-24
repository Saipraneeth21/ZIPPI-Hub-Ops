<?php

namespace Database\Factories\Rental;

use App\Models\Rental\Hub;
use App\Models\Rental\HubStaff;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class HubStaffFactory extends Factory
{
    protected $model = HubStaff::class;

    public function definition(): array
    {
        return [
            'hub_id' => Hub::factory(),
            'name' => fake()->name(),
            'role' => 'staff',
            'employee_code' => 'HUB'.fake()->unique()->numberBetween(1000, 9999),
            'password' => Hash::make('password'),
            'is_active' => true,
        ];
    }
}
