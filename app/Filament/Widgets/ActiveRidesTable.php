<?php

namespace App\Filament\Widgets;

use App\Enums\BookingStatus;
use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Rental\Booking;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Live list of the rides currently out (status = active) — Admin-Dashboard/01-Admin-Modules.md §3.1.
 * Sits under the KPI cards so ops can see, at a glance, who is on a bike right now
 * and which rides are overdue. Polls every 30s to stay current.
 */
class ActiveRidesTable extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Active Rides';

    protected ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        return auth()->user()?->can('orders.view') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => Booking::query()
                    ->with(['user', 'bike', 'pickupHub'])
                    ->where('status', BookingStatus::Active->value)
            )
            ->emptyStateHeading('No active rides')
            ->emptyStateDescription('Bikes currently out will appear here.')
            ->emptyStateIcon('heroicon-o-bolt')
            ->columns([
                TextColumn::make('booking_code')->label('Code')->searchable()->copyable()->weight('medium'),
                TextColumn::make('user.name')->label('Rider')->searchable(),
                TextColumn::make('bike.name')->label('Bike')->searchable(),
                TextColumn::make('pickupHub.name')->label('Pickup hub')->toggleable(),
                TextColumn::make('actual_start_at')
                    ->label('Started')
                    ->state(fn (Booking $record) => $record->actual_start_at ?? $record->start_at)
                    ->dateTime('d M Y, h:i A')
                    ->timezone('Asia/Kolkata')
                    ->sortable(),
                TextColumn::make('end_at')
                    ->label('Due back')
                    ->dateTime('d M Y, h:i A')
                    ->timezone('Asia/Kolkata')
                    ->color(fn (Booking $record) => $record->end_at?->isPast() ? 'danger' : 'gray')
                    ->description(fn (Booking $record) => $record->end_at?->isPast() ? 'Overdue' : null)
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make()->url(fn (Booking $record) => BookingResource::getUrl('view', ['record' => $record])),
            ])
            ->defaultSort('end_at', 'asc')
            ->paginated([10, 25]);
    }
}
