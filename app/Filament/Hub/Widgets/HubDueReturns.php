<?php

namespace App\Filament\Hub\Widgets;

use App\Filament\Hub\Support\HubActions;
use App\Models\Rental\Booking;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/** Dashboard section: active rentals due back (overdue highlighted) at this hub. */
class HubDueReturns extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Due Returns';

    protected ?string $pollingInterval = '60s';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Booking::query()
                ->with(['user', 'bike'])
                ->where('pickup_hub_id', auth('hub')->user()?->hub_id)
                ->where('status', 'active'))
            ->columns([
                TextColumn::make('booking_code')->label('Booking ID')->weight('medium'),
                TextColumn::make('user.name')->label('Customer'),
                TextColumn::make('bike.name')->label('Bike'),
                TextColumn::make('end_at')->label('Time')->dateTime('d M, h:i A')->timezone('Asia/Kolkata')
                    ->color(fn (Booking $record) => $record->end_at?->isPast() ? 'danger' : 'gray')
                    ->description(fn (Booking $record) => $record->end_at?->isPast() ? 'Overdue' : null),
            ])
            ->recordActions([HubActions::completeReturn()])
            ->emptyStateHeading('No due returns')
            ->defaultSort('end_at', 'asc')
            ->paginated([5, 10]);
    }
}
