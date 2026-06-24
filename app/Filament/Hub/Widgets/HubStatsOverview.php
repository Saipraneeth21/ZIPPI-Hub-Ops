<?php

namespace App\Filament\Hub\Widgets;

use App\Filament\Hub\Resources\MaintenanceDue\MaintenanceDueResource;
use App\Models\Rental\Bike;
use App\Models\Rental\Booking;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

/** Hub dashboard KPI cards, scoped to the signed-in staff member's hub. */
class HubStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $hubId = auth('hub')->user()?->hub_id;
        $today = Carbon::now()->toDateString();

        return [
            Stat::make('Available Bikes', Bike::where('hub_id', $hubId)->where('status', 'available')->count())
                ->color('success'),
            Stat::make('Active Rentals', Booking::where('pickup_hub_id', $hubId)->where('status', 'active')->count())
                ->color('warning'),
            Stat::make('Expected Returns Today', Booking::where('pickup_hub_id', $hubId)
                ->where('status', 'active')->whereDate('end_at', $today)->count())
                ->color('primary'),
            Stat::make('Bikes Under Maintenance', Bike::where('hub_id', $hubId)->where('status', 'maintenance')->count())
                ->description('Pulled off the road for repair')
                ->color('danger'),
            Stat::make('Maintenance Due', Bike::where('hub_id', $hubId)->maintenanceDue()->count())
                ->description('In maintenance or service due soon')
                ->color('warning')
                ->url(MaintenanceDueResource::getUrl()),
        ];
    }
}
