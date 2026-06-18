<?php

namespace Database\Seeders;

use App\Enums\BikeStatus;
use App\Enums\BookingStatus;
use App\Enums\KycStatus;
use App\Enums\PaymentStatus;
use App\Models\Rental\Bike;
use App\Models\Rental\BikeTelemetry;
use App\Models\Rental\Booking;
use App\Models\Rental\City;
use App\Models\Rental\GeofenceAlert;
use App\Models\Rental\Hub;
use App\Models\Rental\KycDocument;
use App\Models\Rental\Review;
use App\Models\Rental\Payment;
use App\Models\Rental\UserProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Demo data to visualise the Admin Dashboard (riders, bookings, payments).
 * Idempotent-ish: only seeds when no demo bookings exist. Local/dev only.
 *
 *   php artisan db:seed --class=DemoDashboardSeeder
 */
class DemoDashboardSeeder extends Seeder
{
    public function run(): void
    {
        if (Booking::query()->exists()) {
            $this->command?->warn('Bookings already exist — skipping booking seed.');
            // Still (re)seed dependent demo data in case it is missing.
            $this->seedKycDocuments();
            $this->seedReviews();
            $this->seedTelemetryAndAlerts();

            return;
        }

        $city = City::query()->firstOrFail();
        $hub = Hub::query()->firstOrFail();
        $bikes = Bike::query()->get();

        // 25 riders with mixed KYC states, spread over the last 30 days.
        $kycStates = [
            KycStatus::Approved, KycStatus::Approved, KycStatus::Approved,
            KycStatus::Rejected, KycStatus::Pending, KycStatus::UnderReview, KycStatus::None,
        ];

        $riders = [];
        for ($i = 1; $i <= 25; $i++) {
            $createdAt = Carbon::now()->subDays(random_int(0, 29))->subHours(random_int(0, 23));
            $user = User::create([
                'name' => "Demo Rider {$i}",
                'email' => "rider{$i}@demo.zippi.in",
                'mobile' => '9' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
                'password' => Hash::make('password'),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
            UserProfile::create([
                'user_id' => $user->id,
                'kyc_status' => $kycStates[$i % count($kycStates)]->value,
            ]);
            $riders[] = $user;
        }

        // Bookings across the lifecycle, each with a matching payment.
        $bookingPlan = [
            [BookingStatus::Completed, PaymentStatus::Captured, 11],
            [BookingStatus::Active, PaymentStatus::Captured, 4],
            [BookingStatus::Confirmed, PaymentStatus::Captured, 3],
            [BookingStatus::Cancelled, PaymentStatus::Refunded, 2],
            [BookingStatus::Pending, PaymentStatus::Created, 2],
            [BookingStatus::Expired, PaymentStatus::Failed, 1],
        ];

        $seq = 0;
        foreach ($bookingPlan as [$bStatus, $pStatus, $count]) {
            for ($n = 0; $n < $count; $n++) {
                $seq++;
                $bike = $bikes[$seq % $bikes->count()];
                $rider = $riders[array_rand($riders)];
                $createdAt = Carbon::now()->subDays(random_int(0, 13))->subHours(random_int(0, 23));

                $base = random_int(20, 120) * 1000;     // ₹200–₹1200 in paise
                $tax = (int) round($base * 0.18);
                $deposit = (int) $bike->security_deposit_amount;
                $total = $base + $tax + $deposit;

                $booking = Booking::create([
                    'booking_code' => 'ZB' . strtoupper(Str::random(8)),
                    'user_id' => $rider->id,
                    'bike_id' => $bike->id,
                    'pickup_hub_id' => $hub->id,
                    'return_hub_id' => $hub->id,
                    'city_id' => $city->id,
                    'duration_type' => 'daily',
                    'start_at' => $createdAt->copy()->addHours(2),
                    'end_at' => $createdAt->copy()->addDays(1),
                    'base_amount' => $base,
                    'tax_amount' => $tax,
                    'deposit_amount' => $deposit,
                    'total_amount' => $total,
                    'status' => $bStatus->value,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                Payment::create([
                    'payment_reference' => 'PAY' . strtoupper(Str::random(10)),
                    'booking_id' => $booking->id,
                    'user_id' => $rider->id,
                    'gateway' => 'razorpay',
                    'amount' => $total,
                    'currency' => 'INR',
                    'method' => 'upi',
                    'status' => $pStatus->value,
                    'idempotency_key' => (string) Str::uuid(),
                    'paid_at' => $pStatus === PaymentStatus::Captured ? $createdAt : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                // Reflect active rentals on the fleet for utilisation %.
                if ($bStatus === BookingStatus::Active) {
                    $bike->update(['status' => BikeStatus::Booked->value]);
                }
            }
        }

        $this->command?->info('Seeded ' . count($riders) . ' riders and ' . $seq . ' bookings/payments.');

        // Riders exist now — seed their KYC documents and reviews.
        $this->seedKycDocuments();
        $this->seedReviews();
        $this->seedTelemetryAndAlerts();
    }

    /**
     * Give each active rental a GPS trail near the launch hub, plus a couple of
     * open geofence alerts, so the Live Map renders with data.
     */
    private function seedTelemetryAndAlerts(): void
    {
        if (BikeTelemetry::query()->exists()) {
            return;
        }

        $hub = Hub::query()->firstOrFail();
        $active = Booking::where('status', BookingStatus::Active->value)->with('bike')->get();

        $points = 0;
        foreach ($active as $i => $booking) {
            // Wander outward from the hub for a short trail.
            $lat = (float) $hub->latitude + ($i + 1) * 0.002;
            $lng = (float) $hub->longitude + ($i + 1) * 0.002;

            for ($p = 19; $p >= 0; $p--) {
                $lat += (random_int(-8, 12)) / 10000;
                $lng += (random_int(-8, 12)) / 10000;
                BikeTelemetry::create([
                    'bike_id' => $booking->bike_id,
                    'booking_id' => $booking->id,
                    'latitude' => round($lat, 7),
                    'longitude' => round($lng, 7),
                    'speed_kmph' => random_int(0, 40),
                    'battery_pct' => random_int(40, 100),
                    'ignition' => true,
                    'recorded_at' => Carbon::now()->subMinutes($p * 2),
                ]);
                $points++;
            }

            // Flag the first two active rentals with an open geofence alert.
            if ($i < 2) {
                GeofenceAlert::create([
                    'bike_id' => $booking->bike_id,
                    'booking_id' => $booking->id,
                    'latitude' => round($lat, 7),
                    'longitude' => round($lng, 7),
                    'severity' => $i === 0 ? 'high' : 'medium',
                    'resolved' => false,
                ]);
            }
        }

        $this->command?->info("Seeded {$points} telemetry points and geofence alerts.");
    }

    /** A review on each completed booking (one per booking; booking_id is unique). */
    private function seedReviews(): void
    {
        if (Review::query()->exists()) {
            return;
        }

        $comments = [
            5 => 'Smooth ride, bike was clean and well maintained.',
            4 => 'Good experience overall, pickup was quick.',
            3 => 'Decent, but the bike could be better serviced.',
            2 => 'Bike had some issues, support was helpful though.',
        ];

        $made = 0;
        $completed = Booking::where('status', BookingStatus::Completed->value)->get();
        foreach ($completed as $i => $booking) {
            $rating = [5, 5, 4, 4, 3, 2][$i % 6];
            Review::create([
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'bike_id' => $booking->bike_id,
                'rating' => $rating,
                'comment' => $comments[$rating],
                'status' => $i % 5 === 0 ? 'hidden' : 'published',
            ]);
            $made++;
        }

        $this->command?->info("Seeded {$made} reviews.");
    }

    /**
     * Give every rider who has submitted KYC a couple of documents, with a
     * placeholder file on disk so the signed-URL viewer actually resolves.
     */
    private function seedKycDocuments(): void
    {
        if (KycDocument::query()->exists()) {
            return;
        }

        $disk = Storage::disk(config('filesystems.default'));

        $riders = User::whereHas('profile', fn ($q) => $q->where('kyc_status', '!=', 'none'))
            ->with('profile')
            ->get();

        $docs = 0;
        foreach ($riders as $rider) {
            foreach (['driving_license', 'government_id'] as $type) {
                $path = "kyc/{$rider->id}/{$type}.txt";
                $disk->put($path, "Placeholder {$type} for rider {$rider->id} (demo data).");

                KycDocument::create([
                    'user_id' => $rider->id,
                    'document_type' => $type,
                    'document_number_masked' => strtoupper(substr($type, 0, 2)) . '-XXXX-' . random_int(1000, 9999),
                    'file_path' => $path,
                    'status' => $rider->profile->kyc_status === 'approved' ? 'approved'
                        : ($rider->profile->kyc_status === 'rejected' ? 'rejected' : 'pending'),
                ]);
                $docs++;
            }
        }

        $this->command?->info("Seeded {$docs} KYC documents for {$riders->count()} riders.");
    }
}
