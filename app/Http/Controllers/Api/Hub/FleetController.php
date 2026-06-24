<?php

namespace App\Http\Controllers\Api\Hub;

use App\Models\Rental\Bike;
use Illuminate\Http\Request;

/**
 * Fleet visibility for the staff member's hub. Read-only: no transfers, no
 * inventory management. "reserved" / "active" are derived from current bookings
 * since BikeStatus only models available/booked/maintenance/inactive.
 */
class FleetController extends HubController
{
    public function index(Request $r)
    {
        $status = $r->query('status'); // available|reserved|active|maintenance

        $query = Bike::with(['category'])
            ->where('hub_id', $this->hubId());

        match ($status) {
            'available' => $query->where('status', 'available'),
            'maintenance' => $query->where('status', 'maintenance'),
            'maintenance_due' => $query->maintenanceDue(),
            'reserved' => $query->whereHas('bookings', fn ($b) => $b->where('status', 'confirmed')),
            'active' => $query->whereHas('bookings', fn ($b) => $b->where('status', 'active')),
            default => null,
        };

        $bikes = $query->orderBy('registration_no')
            ->get()
            ->map(fn (Bike $bike) => $this->bikeRow($bike))
            ->values();

        return $this->ok($bikes);
    }

    public function show(Bike $bike)
    {
        abort_unless((int) $bike->hub_id === (int) $this->hubId(), 404);
        $bike->load(['category', 'hub']);

        return $this->ok($this->bikeRow($bike) + [
            'hub' => $bike->hub?->name,
            'fuel_type' => $bike->fuel_type,
            'year' => $bike->year,
            'specifications' => $bike->specifications,
        ]);
    }

    private function bikeRow(Bike $bike): array
    {
        return [
            'id' => $bike->id,
            'vehicle_number' => $bike->registration_no,
            'name' => $bike->name,
            'category' => $bike->category?->name,
            'status' => $bike->status,
            'current_status' => $this->derivedStatus($bike),
        ];
    }

    /** Display status: maintenance > active ride > reserved > available. */
    private function derivedStatus(Bike $bike): string
    {
        if ($bike->status === 'maintenance' || $bike->status === 'inactive') {
            return $bike->status;
        }
        if ($bike->bookings()->where('status', 'active')->exists()) {
            return 'active_ride';
        }
        if ($bike->bookings()->where('status', 'confirmed')->exists()) {
            return 'reserved';
        }

        return 'available';
    }
}
