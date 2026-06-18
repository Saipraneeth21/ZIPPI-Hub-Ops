<?php

namespace App\Services\Rental;

use App\Models\Rental\Bike;
use App\Models\Rental\Booking;
use Carbon\CarbonInterface;

/**
 * Availability + overlap protection. A bike must not have two pending/confirmed/
 * active bookings whose [start, end) windows overlap.
 * See Database/03-Data-Relationships.md §2.1.
 */
class AvailabilityService
{
    private const BLOCKING_STATUSES = ['pending', 'confirmed', 'active'];

    public function isAvailable(int $bikeId, CarbonInterface $start, CarbonInterface $end, ?int $ignoreBookingId = null): bool
    {
        $bike = Bike::find($bikeId);
        if (! $bike || in_array($bike->status, ['maintenance', 'inactive'], true)) {
            return false;
        }

        return ! $this->overlapQuery($bikeId, $start, $end, $ignoreBookingId)->exists();
    }

    /**
     * Lock the candidate rows FOR UPDATE and assert no overlap. Must be called
     * inside a DB transaction. Returns true if the window can be reserved.
     */
    public function lockAndCheck(int $bikeId, CarbonInterface $start, CarbonInterface $end): bool
    {
        $exists = $this->overlapQuery($bikeId, $start, $end)
            ->lockForUpdate()
            ->exists();

        return ! $exists;
    }

    private function overlapQuery(int $bikeId, CarbonInterface $start, CarbonInterface $end, ?int $ignoreBookingId = null)
    {
        $q = Booking::query()
            ->where('bike_id', $bikeId)
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start);

        if ($ignoreBookingId) {
            $q->where('id', '!=', $ignoreBookingId);
        }

        return $q;
    }
}
