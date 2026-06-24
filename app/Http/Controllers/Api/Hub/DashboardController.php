<?php

namespace App\Http\Controllers\Api\Hub;

use App\Models\Rental\Bike;
use App\Models\Rental\Booking;
use Illuminate\Support\Carbon;

/**
 * Hub dashboard: live counts + the operational lists hub staff act on.
 * Notifications are derived here (no separate store): "upcoming pickups" and
 * "due returns" double as the staff's operational alerts.
 */
class DashboardController extends HubController
{
    public function index()
    {
        $hubId = $this->hubId();
        $today = Carbon::now();

        $cards = [
            'available_bikes' => Bike::where('hub_id', $hubId)->where('status', 'available')->count(),
            'active_rentals' => Booking::where('pickup_hub_id', $hubId)->where('status', 'active')->count(),
            'expected_returns_today' => Booking::where('pickup_hub_id', $hubId)
                ->where('status', 'active')
                ->whereDate('end_at', $today->toDateString())
                ->count(),
            'bikes_under_maintenance' => Bike::where('hub_id', $hubId)->where('status', 'maintenance')->count(),
            'maintenance_due' => Bike::where('hub_id', $hubId)->maintenanceDue()->count(),
        ];

        $upcomingPickups = Booking::with(['user', 'bike'])
            ->where('pickup_hub_id', $hubId)
            ->where('status', 'confirmed')
            ->orderBy('start_at')
            ->limit(50)
            ->get()
            ->map(fn (Booking $b) => $this->bookingRow($b))
            ->values();

        $dueReturns = Booking::with(['user', 'bike'])
            ->where('pickup_hub_id', $hubId)
            ->where('status', 'active')
            ->orderBy('end_at')
            ->limit(50)
            ->get()
            ->map(function (Booking $b) use ($today) {
                $row = $this->bookingRow($b);
                $row['is_overdue'] = $b->end_at !== null && $b->end_at->lessThan($today);

                return $row;
            })
            ->values();

        return $this->ok([
            'cards' => $cards,
            'upcoming_pickups' => $upcomingPickups,
            'due_returns' => $dueReturns,
        ]);
    }
}
