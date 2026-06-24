<?php

namespace App\Http\Controllers\Api\Hub;

use App\Models\Rental\Bike;
use App\Services\Rental\HubOpsService;
use Illuminate\Http\Request;

/** Hub-side maintenance ticketing (reuses the MaintenanceRecord entity). */
class MaintenanceController extends HubController
{
    public function __construct(private readonly HubOpsService $hubOps) {}

    public function store(Request $r)
    {
        $data = $r->validate([
            'bike_id' => ['required', 'integer'],
            'category' => ['required', 'string', 'in:Battery,Tyre,Brake,Motor,Body Damage,Other'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', 'in:open,in_progress,completed'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['file', 'image', 'max:5120'],
        ]);

        $bike = Bike::where('id', $data['bike_id'])->where('hub_id', $this->hubId())->first();
        if (! $bike) {
            return $this->fail('Bike not found in your hub.', null, 404);
        }

        $record = $this->hubOps->reportMaintenance($bike->id, $this->staff()->id, [
            'category' => $data['category'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'open',
            'photos' => $this->storePhotos($r->file('photos'), 'maintenance'),
        ]);

        return $this->created([
            'id' => $record->id,
            'bike_id' => $bike->id,
            'category' => $record->maintenance_type,
            'status' => $record->status,
        ], 'Maintenance ticket created');
    }
}
