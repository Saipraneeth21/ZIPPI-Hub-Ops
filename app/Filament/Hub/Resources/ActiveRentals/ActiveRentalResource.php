<?php

namespace App\Filament\Hub\Resources\ActiveRentals;

use App\Filament\Hub\Resources\ActiveRentals\Pages\ListActiveRentals;
use App\Filament\Hub\Support\HubActions;
use App\Models\Rental\Booking;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** Active rentals: bikes currently out, scoped to the staff hub, with return workflow. */
class ActiveRentalResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $slug = 'active-rentals';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static ?string $navigationLabel = 'Active Rentals';

    protected static string|UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'bike'])
            ->where('pickup_hub_id', auth('hub')->user()?->hub_id)
            ->where('status', 'active');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking_code')->label('Booking ID')->searchable()->copyable()->weight('medium'),
                TextColumn::make('user.name')->label('Customer')->searchable(),
                TextColumn::make('bike.name')->label('Bike')->searchable(),
                TextColumn::make('end_at')->label('Expected return')
                    ->dateTime('d M Y, h:i A')->timezone('Asia/Kolkata')
                    ->color(fn (Booking $record) => $record->end_at?->isPast() ? 'danger' : 'gray')
                    ->description(fn (Booking $record) => $record->end_at?->isPast() ? 'Overdue' : null)
                    ->sortable(),
            ])
            ->recordActions([
                HubActions::completeReturn(),
            ])
            ->emptyStateHeading('No active rentals')
            ->defaultSort('end_at', 'asc');
    }

    public static function getPages(): array
    {
        return ['index' => ListActiveRentals::route('/')];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
