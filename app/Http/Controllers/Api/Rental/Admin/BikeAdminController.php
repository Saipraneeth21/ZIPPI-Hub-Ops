<?php
namespace App\Http\Controllers\Api\Rental\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rental\Bike;
use App\Models\Rental\BikePricing;
use App\Support\Concerns\ApiResponse;
use Illuminate\Http\Request;

class BikeAdminController extends Controller
{
    use ApiResponse;

    public function store(Request $r)
    {
        $data = $r->validate([
            'category_id' => ['required', 'exists:rental_bike_categories,id'],
            'hub_id' => ['required', 'exists:rental_hubs,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'name' => ['required', 'string', 'max:150'],
            'registration_no' => ['required', 'string', 'unique:rental_bikes,registration_no'],
            'iot_device_id' => ['nullable', 'string', 'unique:rental_bikes,iot_device_id'],
            'fuel_type' => ['required', 'in:petrol,electric'],
            'security_deposit_amount' => ['required', 'integer', 'min:0'],
            'specifications' => ['nullable', 'array'],
            'hourly_rate' => ['nullable', 'integer', 'min:0'],
            'daily_rate' => ['nullable', 'integer', 'min:0'],
            'monthly_rate' => ['nullable', 'integer', 'min:0'],
        ]);
        $bike = Bike::create(collect($data)->except(['hourly_rate', 'daily_rate', 'monthly_rate'])->all() + ['status' => 'available']);
        BikePricing::create([
            'bike_id' => $bike->id,
            'hourly_rate' => $data['hourly_rate'] ?? null,
            'daily_rate' => $data['daily_rate'] ?? null,
            'monthly_rate' => $data['monthly_rate'] ?? null,
            'min_hours' => 1,
            'is_active' => true,
        ]);
        return $this->created(['id' => $bike->id], 'Bike created');
    }

    public function updateStatus(Request $r, Bike $bike)
    {
        $data = $r->validate(['status' => ['required', 'in:available,booked,maintenance,inactive']]);
        $bike->update($data);
        return $this->ok(null, 'Status updated');
    }
}
