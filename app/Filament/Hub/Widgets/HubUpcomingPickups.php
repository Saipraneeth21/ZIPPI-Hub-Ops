<?php

namespace App\Filament\Hub\Widgets;

use App\Filament\Hub\Support\HubActions;
use App\Models\Rental\Booking;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/** Dashboard section: confirmed bookings awaiting handover at this hub. */
class HubUpcomingPickups extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Upcoming Pickups';

    protected ?string $pollingInterval = '60s';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Booking::query()
                ->with(['user', 'bike'])
                ->where('pickup_hub_id', auth('hub')->user()?->hub_id)
                ->where('status', 'confirmed'))
            ->columns([
                TextColumn::make('booking_code')->label('Booking ID')->weight('medium'),
                TextColumn::make('user.name')->label('Customer'),
                TextColumn::make('bike.name')->label('Bike'),
                TextColumn::make('start_at')->label('Time')->dateTime('d M, h:i A')->timezone('Asia/Kolkata'),
            ])
            ->recordActions([HubActions::handover()])
            ->emptyStateHeading('No upcoming pickups')
            ->defaultSort('start_at', 'asc')
            ->paginated([5, 10]);
    }
}
