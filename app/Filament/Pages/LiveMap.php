<?php

namespace App\Filament\Pages;

use App\Models\Rental\Hub;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Live fleet map (Admin-Dashboard §3.7). Renders Leaflet and polls
 * /admin/tracking/positions for active-rental markers, trails, and open
 * geofence alerts. Realtime today is polling; the endpoint shape is ready for
 * a Pusher/WebSockets swap.
 */
class LiveMap extends Page
{
    protected string $view = 'filament.pages.live-map';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;


    protected static ?string $navigationLabel = 'Live Map';

    protected static string|UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('tracking.view') ?? false;
    }

    /** Default map centre — the first active hub, falling back to Hyderabad. */
    public function getCenter(): array
    {
        $hub = Hub::query()->where('is_active', true)->first();

        return [
            'lat' => $hub?->latitude ?? 17.3850,
            'lng' => $hub?->longitude ?? 78.4867,
        ];
    }

    public function getFeedUrl(): string
    {
        return route('admin.tracking.positions');
    }
}
