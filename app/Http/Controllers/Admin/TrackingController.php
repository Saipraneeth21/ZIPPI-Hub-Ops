<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rental\Booking;
use App\Models\Rental\GeofenceAlert;
use Illuminate\Http\JsonResponse;

/**
 * Live fleet positions for the admin tracking map (Admin-Dashboard §3.7).
 * Returns the latest telemetry point + recent trail for each active rental,
 * plus open geofence alerts. Polled by the Live Map page; swap the poll for a
 * Pusher/WebSockets channel later without changing the payload shape.
 */
class TrackingController extends Controller
{
    public const TRAIL_POINTS = 20;

    public function positions(): JsonResponse
    {
        abort_unless(auth('admin')->user()?->can('tracking.view'), 403);

        $bookings = Booking::query()
            ->where('status', 'active')
            ->with(['user:id,name', 'bike:id,name,registration_no'])
            ->get();

        $rentals = $bookings->map(function (Booking $booking) {
            $points = $booking->bike?->telemetry()
                ->latest('recorded_at')
                ->limit(self::TRAIL_POINTS)
                ->get(['latitude', 'longitude', 'recorded_at', 'speed_kmph', 'battery_pct']);

            if (! $points || $points->isEmpty()) {
                return null;
            }

            $latest = $points->first();

            return [
                'booking_id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'rider' => $booking->user?->name,
                'bike' => $booking->bike?->name,
                'registration_no' => $booking->bike?->registration_no,
                'end_at' => $booking->end_at?->timezone('Asia/Kolkata')->format('d M, h:i A'),
                'lat' => $latest->latitude,
                'lng' => $latest->longitude,
                'speed' => $latest->speed_kmph,
                'battery' => $latest->battery_pct,
                // Trail oldest -> newest for a clean polyline.
                'trail' => $points->reverse()->map(fn ($p) => [$p->latitude, $p->longitude])->values(),
            ];
        })->filter()->values();

        $alerts = GeofenceAlert::query()
            ->where('resolved', false)
            ->with('bike:id,name')
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (GeofenceAlert $a) => [
                'id' => $a->id,
                'bike' => $a->bike?->name,
                'severity' => $a->severity,
                'lat' => $a->latitude,
                'lng' => $a->longitude,
            ]);

        return response()->json([
            'rentals' => $rentals,
            'alerts' => $alerts,
            'counts' => [
                'active' => $rentals->count(),
                'open_alerts' => $alerts->count(),
            ],
        ]);
    }
}
