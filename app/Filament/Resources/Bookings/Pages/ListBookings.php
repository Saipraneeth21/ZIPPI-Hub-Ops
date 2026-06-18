<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Enums\BookingStatus;
use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Rental\Booking;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    /** Lifecycle tabs (§3.5): Upcoming / Active / Completed / Cancelled. */
    public function getTabs(): array
    {
        $upcoming = [BookingStatus::Pending->value, BookingStatus::Confirmed->value];
        $cancelled = [BookingStatus::Cancelled->value, BookingStatus::Expired->value];

        return [
            'all' => Tab::make('All'),
            'upcoming' => Tab::make('Upcoming')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', $upcoming))
                ->badge($this->countByStatuses($upcoming)),
            'active' => Tab::make('Active')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', BookingStatus::Active->value))
                ->badge($this->countByStatuses([BookingStatus::Active->value])),
            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', BookingStatus::Completed->value)),
            'cancelled' => Tab::make('Cancelled')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', $cancelled)),
        ];
    }

    private function countByStatuses(array $statuses): ?string
    {
        $count = Booking::whereIn('status', $statuses)->count();

        return $count > 0 ? (string) $count : null;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
