<?php

namespace App\Filament\Hub\Resources\Fleet;

use App\Filament\Hub\Resources\Fleet\Pages\ListFleet;
use App\Filament\Hub\Support\HubActions;
use App\Models\Rental\Bike;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** Fleet visibility for the staff hub (read-only) + maintenance/incident reporting. */
class FleetResource extends Resource
{
    protected static ?string $model = Bike::class;

    protected static ?string $slug = 'fleet';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?string $navigationLabel = 'Fleet';

    protected static string|UnitEnum|null $navigationGroup = 'Fleet';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['category'])
            ->where('hub_id', auth('hub')->user()?->hub_id);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('registration_no')->label('Vehicle number')->searchable()->weight('medium'),
                TextColumn::make('name')->label('Bike')->searchable(),
                TextColumn::make('category.name')->label('Category')->badge(),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn (string $state) => match ($state) {
                        'available' => 'success',
                        'booked' => 'warning',
                        'maintenance' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('fleet_status')
                    ->label('Status')
                    ->options([
                        'available' => 'Available',
                        'reserved' => 'Reserved',
                        'active' => 'Active Ride',
                        'maintenance' => 'Maintenance',
                        'maintenance_due' => 'Maintenance due',
                    ])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'available' => $query->where('status', 'available'),
                        'maintenance' => $query->where('status', 'maintenance'),
                        'maintenance_due' => $query->maintenanceDue(),
                        'reserved' => $query->whereHas('bookings', fn ($b) => $b->where('status', 'confirmed')),
                        'active' => $query->whereHas('bookings', fn ($b) => $b->where('status', 'active')),
                        default => $query,
                    }),
            ])
            ->recordActions([
                HubActions::reportMaintenance(),
                HubActions::reportIncident(),
            ])
            ->emptyStateHeading('No bikes in your hub')
            ->defaultSort('registration_no', 'asc');
    }

    public static function getPages(): array
    {
        return ['index' => ListFleet::route('/')];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
