<?php

namespace App\Http\Controllers\Api\Hub;

use App\Http\Controllers\Controller;
use App\Models\Rental\Booking;
use App\Models\Rental\HubStaff;
use App\Support\Concerns\ApiResponse;
use Illuminate\Http\UploadedFile;

/**
 * Base for all Hub Operations API controllers. Resolves the authenticated hub
 * staff + their hub, and centralises the hub-scoping rule so no controller has
 * to repeat it.
 */
abstract class HubController extends Controller
{
    use ApiResponse;

    protected function staff(): HubStaff
    {
        /** @var HubStaff $staff */
        $staff = request()->user();

        return $staff;
    }

    protected function hubId(): ?int
    {
        return $this->staff()->hub_id;
    }

    /**
     * Persist uploaded photos to the public disk and return relative paths.
     *
     * @param  UploadedFile[]|null  $files
     * @return string[]
     */
    protected function storePhotos(?array $files, string $dir): array
    {
        $paths = [];
        foreach ($files ?? [] as $file) {
            if ($file instanceof UploadedFile) {
                $paths[] = $file->store("hub/{$dir}", 'public');
            }
        }

        return $paths;
    }

    /** Compact booking line used by dashboard, pickups and active-rentals lists. */
    protected function bookingRow(Booking $b): array
    {
        return [
            'id' => $b->id,
            'booking_id' => $b->booking_code,
            'customer_name' => $b->user?->name,
            'customer_mobile' => $b->user?->mobile,
            'bike' => $b->bike ? trim($b->bike->name.' ('.$b->bike->registration_no.')') : null,
            'status' => $b->status,
            'start_at' => $b->start_at?->toIso8601String(),
            'end_at' => $b->end_at?->toIso8601String(),
        ];
    }
}
