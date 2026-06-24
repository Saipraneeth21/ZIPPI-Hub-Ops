<?php

namespace App\Http\Controllers\Api\Hub;

use App\Models\Rental\Bike;
use App\Models\Rental\Booking;
use App\Services\Rental\HubOpsService;
use Illuminate\Http\Request;

/** Hub-side incident reporting (reuses the IncidentReport entity). */
class IncidentController extends HubController
{
    public function __construct(private readonly HubOpsService $hubOps) {}

    public function store(Request $r)
    {
        $data = $r->validate([
            'bike_id' => ['required', 'integer'],
            'booking_id' => ['nullable', 'integer'],
            'incident_type' => ['required', 'string', 'in:Accident,Breakdown,Damage,Theft'],
            'severity' => ['nullable', 'in:minor,moderate,severe,total_loss'],
            'description' => ['required', 'string', 'max:2000'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['file', 'image', 'max:5120'],
        ]);

        $bike = Bike::where('id', $data['bike_id'])->where('hub_id', $this->hubId())->first();
        if (! $bike) {
            return $this->fail('Bike not found in your hub.', null, 404);
        }

        // If a booking is linked, it must belong to this hub too.
        if (! empty($data['booking_id'])) {
            $owned = Booking::where('id', $data['booking_id'])
                ->where('pickup_hub_id', $this->hubId())
                ->exists();
            if (! $owned) {
                return $this->fail('Booking not found in your hub.', null, 404);
            }
        }

        $record = $this->hubOps->reportIncident($bike->id, $this->staff()->id, [
            'booking_id' => $data['booking_id'] ?? null,
            'incident_type' => $data['incident_type'],
            'severity' => $data['severity'] ?? 'minor',
            'description' => $data['description'],
            'reported_by' => $this->staff()->name,
            'photos' => $this->storePhotos($r->file('photos'), 'incidents'),
        ]);

        return $this->created([
            'id' => $record->id,
            'bike_id' => $bike->id,
            'incident_type' => $record->incident_type,
            'severity' => $record->severity,
            'status' => $record->status,
        ], 'Incident reported');
    }
}
