<?php

namespace App\Http\Controllers\Api\Hub;

use App\Models\Rental\Booking;
use App\Services\Rental\HubOpsService;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Hub-side booking operations: search/lookup, pickup handover, and bike return.
 * Lifecycle + money math are delegated to HubOpsService → BookingService; this
 * controller only validates input and enforces hub scoping.
 */
class BookingController extends HubController
{
    public function __construct(private readonly HubOpsService $hubOps) {}

    /** Search this hub's bookings by booking code or customer mobile. */
    public function search(Request $r)
    {
        $q = trim((string) $r->query('q', ''));
        if ($q === '') {
            return $this->fail('Provide a booking ID or mobile number to search.', null, 422);
        }

        $bookings = Booking::with(['user', 'bike'])
            ->where('pickup_hub_id', $this->hubId())
            ->where(function ($query) use ($q) {
                $query->where('booking_code', 'like', "%{$q}%")
                    ->orWhereHas('user', fn ($u) => $u->where('mobile', 'like', "%{$q}%"));
            })
            ->orderByDesc('id')
            ->limit(25)
            ->get()
            ->map(fn (Booking $b) => $this->bookingRow($b))
            ->values();

        return $this->ok($bookings);
    }

    public function show(Booking $booking)
    {
        $this->authorizeHub($booking);
        $booking->load(['user.profile', 'bike.category', 'pickupHub']);

        return $this->ok([
            'booking' => [
                'id' => $booking->id,
                'booking_id' => $booking->booking_code,
                'status' => $booking->status,
                'start_at' => $booking->start_at?->toIso8601String(),
                'end_at' => $booking->end_at?->toIso8601String(),
                'duration_type' => $booking->duration_type,
                'total_amount' => (int) $booking->total_amount,
                'deposit_amount' => (int) $booking->deposit_amount,
            ],
            'customer' => [
                'name' => $booking->user?->name,
                'mobile' => $booking->user?->mobile,
                'email' => $booking->user?->email,
                'kyc_status' => $booking->user?->profile?->kyc_status ?? 'none',
            ],
            'bike' => $booking->bike ? [
                'id' => $booking->bike->id,
                'name' => $booking->bike->name,
                'registration_no' => $booking->bike->registration_no,
                'category' => $booking->bike->category?->name,
                'status' => $booking->bike->status,
            ] : null,
        ]);
    }

    public function pickups()
    {
        $rows = Booking::with(['user', 'bike'])
            ->where('pickup_hub_id', $this->hubId())
            ->where('status', 'confirmed')
            ->orderBy('start_at')
            ->get()
            ->map(fn (Booking $b) => $this->bookingRow($b))
            ->values();

        return $this->ok($rows);
    }

    public function activeRentals()
    {
        $rows = Booking::with(['user', 'bike'])
            ->where('pickup_hub_id', $this->hubId())
            ->where('status', 'active')
            ->orderBy('end_at')
            ->get()
            ->map(fn (Booking $b) => $this->bookingRow($b))
            ->values();

        return $this->ok($rows);
    }

    public function handover(Request $r, Booking $booking)
    {
        $this->authorizeHub($booking);

        $data = $r->validate([
            'battery_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'checklist' => ['nullable', 'array'],
            'checklist.bike_inspected' => ['nullable', 'boolean'],
            'checklist.helmet_issued' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['file', 'image', 'max:5120'],
        ]);

        try {
            $handover = $this->hubOps->handover($booking, $this->staff()->id, [
                'battery_percent' => $data['battery_percent'] ?? null,
                'checklist' => $data['checklist'] ?? null,
                'notes' => $data['notes'] ?? null,
                'photos' => $this->storePhotos($r->file('photos'), 'handovers'),
            ]);
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), null, 422);
        }

        return $this->ok([
            'booking_id' => $booking->booking_code,
            'status' => $booking->fresh()->status,
            'handover_id' => $handover->id,
        ], 'Bike handed over');
    }

    public function returnBike(Request $r, Booking $booking)
    {
        $this->authorizeHub($booking);

        $data = $r->validate([
            'odometer_reading' => ['nullable', 'integer', 'min:0'],
            'battery_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'damage_notes' => ['nullable', 'string', 'max:1000'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['file', 'image', 'max:5120'],
        ]);

        try {
            $outcome = $this->hubOps->completeReturn($booking, $this->staff()->id, [
                'odometer_reading' => $data['odometer_reading'] ?? null,
                'battery_percent' => $data['battery_percent'] ?? null,
                'damage_notes' => $data['damage_notes'] ?? null,
                'photos' => $this->storePhotos($r->file('photos'), 'returns'),
            ]);
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), null, 422);
        }

        return $this->ok([
            'booking_id' => $booking->booking_code,
            'status' => $outcome['result']['status'],
            'late_penalty' => $outcome['result']['late_penalty'],
            'deposit_refund' => $outcome['result']['deposit_refund'],
            'return_id' => $outcome['capture']->id,
        ], 'Return completed');
    }

    /** 404 a booking that does not belong to the staff member's hub. */
    private function authorizeHub(Booking $booking): void
    {
        abort_unless((int) $booking->pickup_hub_id === (int) $this->hubId(), 404);
    }
}
