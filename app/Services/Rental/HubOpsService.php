<?php

namespace App\Services\Rental;

use App\Models\Rental\Booking;
use App\Models\Rental\HubHandover;
use App\Models\Rental\HubReturn;
use App\Models\Rental\IncidentReport;
use App\Models\Rental\MaintenanceRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Hub Operations orchestration. Deliberately thin: it delegates all booking
 * lifecycle + money math to the existing BookingService and only persists the
 * additive hub-side capture (photos, battery, odometer, checklist, tickets).
 * Shared by the Hub Ops API controllers and the /hub Filament panel actions so
 * the two never drift.
 */
class HubOpsService
{
    public function __construct(private readonly BookingService $bookings) {}

    /**
     * Hand a bike over to a rider: delegate the confirmed→active transition to
     * BookingService::unlock (which enforces the state machine + notifies the
     * rider), then record the handover capture.
     */
    public function handover(Booking $booking, ?int $hubStaffId, array $capture): HubHandover
    {
        return DB::transaction(function () use ($booking, $hubStaffId, $capture) {
            $this->bookings->unlock($booking);

            return HubHandover::create([
                'booking_id' => $booking->id,
                'hub_staff_id' => $hubStaffId,
                'battery_percent' => $capture['battery_percent'] ?? null,
                'checklist' => $capture['checklist'] ?? null,
                'photos' => $capture['photos'] ?? null,
                'notes' => $capture['notes'] ?? null,
            ]);
        });
    }

    /**
     * Complete a return: delegate the active→completed transition + late penalty
     * + deposit refund to BookingService::returnBike, then record the return
     * capture. adminId is passed as null (the actor is recorded on hub_returns).
     *
     * @return array{result: array, capture: HubReturn}
     */
    public function completeReturn(Booking $booking, ?int $hubStaffId, array $capture): array
    {
        return DB::transaction(function () use ($booking, $hubStaffId, $capture) {
            $result = $this->bookings->returnBike($booking, $capture['return_hub_id'] ?? null, null);

            $record = HubReturn::create([
                'booking_id' => $booking->id,
                'hub_staff_id' => $hubStaffId,
                'odometer_reading' => $capture['odometer_reading'] ?? null,
                'battery_percent' => $capture['battery_percent'] ?? null,
                'photos' => $capture['photos'] ?? null,
                'damage_notes' => $capture['damage_notes'] ?? null,
            ]);

            return ['result' => $result, 'capture' => $record];
        });
    }

    /** File a maintenance ticket (reuses the existing MaintenanceRecord entity). */
    public function reportMaintenance(int $bikeId, ?int $hubStaffId, array $data): MaintenanceRecord
    {
        return MaintenanceRecord::create([
            'bike_id' => $bikeId,
            'maintenance_type' => $data['category'],
            'status' => $data['status'] ?? 'open',
            'description' => $data['description'] ?? null,
            'reported_by_hub_staff_id' => $hubStaffId,
            'maintenance_date' => Carbon::now()->toDateString(),
            'cost' => 0,
            'attachments' => $data['photos'] ?? null,
        ]);
    }

    /** File an incident report (reuses the existing IncidentReport entity). */
    public function reportIncident(int $bikeId, ?int $hubStaffId, array $data): IncidentReport
    {
        return IncidentReport::create([
            'bike_id' => $bikeId,
            'booking_id' => $data['booking_id'] ?? null,
            'incident_type' => $data['incident_type'],
            'severity' => $data['severity'] ?? 'minor',
            'status' => $data['status'] ?? 'open',
            'incident_date' => Carbon::now()->toDateString(),
            'description' => $data['description'],
            'reported_by' => $data['reported_by'] ?? null,
            'reported_by_hub_staff_id' => $hubStaffId,
            'photos' => $data['photos'] ?? null,
        ]);
    }
}
