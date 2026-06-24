<?php

namespace App\Filament\Hub\Resources\Pickups;

use App\Filament\Hub\Resources\Pickups\Pages\ListPickups;
use App\Filament\Hub\Support\HubActions;
use App\Models\Rental\Booking;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** Upcoming pickups: confirmed bookings awaiting handover, scoped to the staff hub. */
class PickupResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $slug = 'pickups';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static ?string $navigationLabel = 'Upcoming Pickups';

    protected static string|UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'bike'])
            ->where('pickup_hub_id', auth('hub')->user()?->hub_id)
            ->where('status', 'confirmed');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking_code')->label('Booking ID')->searchable()->copyable()->weight('medium'),
                TextColumn::make('user.name')->label('Customer')->searchable(),
                TextColumn::make('user.mobile')->label('Mobile')->searchable()->toggleable(),
                TextColumn::make('bike.name')->label('Bike')->searchable(),
                TextColumn::make('start_at')->label('Pickup time')
                    ->dateTime('d M Y, h:i A')->timezone('Asia/Kolkata')->sortable(),
            ])
            ->recordActions([
                HubActions::handover(),
            ])
            ->emptyStateHeading('No upcoming pickups')
            ->defaultSort('start_at', 'asc');
    }

    public static function getPages(): array
    {
        return ['index' => ListPickups::route('/')];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
