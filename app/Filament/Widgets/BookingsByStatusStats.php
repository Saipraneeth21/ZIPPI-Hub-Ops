<?php

namespace App\Filament\Widgets;

use App\Enums\BookingStatus;
use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Rental\Booking;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Bookings broken down by lifecycle status as clickable stat blocks (§3.1).
 * Each block deep-links into the Orders list filtered to that status.
 */
class BookingsByStatusStats extends StatsOverviewWidget
{
    protected static ?int $sort = 5;

    protected ?string $pollingInterval = '60s';

    protected ?string $heading = 'Bookings by Status';

    /** status => [label, colour, icon] */
    private const STATUSES = [
        BookingStatus::Pending->value => ['Pending', 'gray', 'heroicon-m-clock'],
        BookingStatus::Confirmed->value => ['Confirmed', 'info', 'heroicon-m-check'],
        BookingStatus::Active->value => ['Active', 'warning', 'heroicon-m-bolt'],
        BookingStatus::Completed->value => ['Completed', 'success', 'heroicon-m-check-circle'],
        BookingStatus::Cancelled->value => ['Cancelled', 'danger', 'heroicon-m-x-circle'],
        BookingStatus::Expired->value => ['Expired', 'gray', 'heroicon-m-no-symbol'],
    ];

    protected function getStats(): array
    {
        $counts = Booking::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $canView = auth()->user()?->can('orders.view') ?? false;

        $stats = [];
        foreach (self::STATUSES as $status => [$label, $color, $icon]) {
            $count = (int) ($counts[$status] ?? 0);

            $stats[] = Stat::make($label, (string) $count)
                ->description('Bookings')
                ->descriptionIcon($icon)
                ->color($color)
                ->url($canView
                    ? BookingResource::getUrl('index', ['tableFilters' => ['status' => ['value' => $status]]])
                    : null);
        }

        return $stats;
    }
}
