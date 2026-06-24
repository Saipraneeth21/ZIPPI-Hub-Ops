<?php

namespace Database\Seeders;

use App\Models\Rental\AdminUser;
use App\Models\Rental\Bike;
use App\Models\Rental\BikeCategory;
use App\Models\Rental\BikePricing;
use App\Models\Rental\City;
use App\Models\Rental\Coupon;
use App\Models\Rental\Hub;
use App\Models\Rental\HubStaff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Idempotent: if the core data is already seeded, do nothing. Lets the
        // container safely run this on every boot (no Shell access on free tier).
        if (AdminUser::query()->exists()) {
            return;
        }

        $city = City::create(['name' => 'Hyderabad', 'state' => 'TS', 'country' => 'IN', 'timezone' => 'Asia/Kolkata']);

        $hub = Hub::create([
            'city_id' => $city->id, 'name' => 'Hitech City Hub', 'address' => 'HITEC City, Madhapur',
            'latitude' => 17.4435, 'longitude' => 78.3772, 'is_active' => true,
        ]);

        $categories = [
            ['name' => 'Scooter', 'slug' => 'scooter', 'default_deposit_amount' => 200000],
            ['name' => 'Geared', 'slug' => 'geared', 'default_deposit_amount' => 300000],
            ['name' => 'Electric', 'slug' => 'electric', 'default_deposit_amount' => 250000],
        ];

        foreach ($categories as $i => $cat) {
            $category = BikeCategory::create($cat + ['is_active' => true, 'sort_order' => $i]);
            for ($n = 1; $n <= 4; $n++) {
                $bike = Bike::create([
                    'category_id' => $category->id,
                    'hub_id' => $hub->id,
                    'city_id' => $city->id,
                    'name' => $cat['name'] . " Model {$n}",
                    'registration_no' => 'TS09' . strtoupper(substr($cat['slug'], 0, 2)) . str_pad((string) $n, 4, '0', STR_PAD_LEFT),
                    'iot_device_id' => 'IOT-' . $cat['slug'] . "-{$n}",
                    'fuel_type' => $cat['slug'] === 'electric' ? 'electric' : 'petrol',
                    'year' => 2024,
                    'specifications' => ['engine_cc' => 110, 'mileage_kmpl' => 45, 'seats' => 2],
                    'status' => 'available',
                    'security_deposit_amount' => $cat['default_deposit_amount'],
                ]);
                BikePricing::create([
                    'bike_id' => $bike->id,
                    'hourly_rate' => 3000, 'daily_rate' => 40000, 'monthly_rate' => 700000,
                    'min_hours' => 2, 'is_active' => true,
                ]);
            }
        }

        Coupon::create([
            'code' => 'ZIPPI100', 'type' => 'flat', 'value' => 10000, 'min_booking_amount' => 50000,
            'usage_limit_per_user' => 1, 'usage_limit_total' => 1000, 'used_count' => 0,
            'valid_from' => now()->subDay(), 'valid_to' => now()->addMonths(3), 'is_active' => true,
        ]);

        AdminUser::create([
            'name' => 'Super Admin', 'email' => 'admin@zippi.in',
            'password' => Hash::make('password'), 'role' => 'super_admin', 'is_active' => true,
        ]);
        AdminUser::create([
            'name' => 'KYC Reviewer', 'email' => 'kyc@zippi.in',
            'password' => Hash::make('password'), 'role' => 'kyc_reviewer', 'is_active' => true,
        ]);

        // Hub Operations — demo on-site staff for the /hub panel + Hub Ops API.
        HubStaff::create([
            'hub_id' => $hub->id, 'name' => 'Hub Manager', 'role' => 'manager',
            'employee_code' => 'HUB001', 'password' => Hash::make('password'), 'is_active' => true,
        ]);
    }
}
