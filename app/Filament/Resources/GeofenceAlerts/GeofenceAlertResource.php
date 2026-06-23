<?php

namespace App\Filament\Resources\GeofenceAlerts;

use App\Filament\Resources\GeofenceAlerts\Pages\ListGeofenceAlerts;
use App\Filament\Resources\GeofenceAlerts\Tables\GeofenceAlertsTable;
use App\Models\Rental\GeofenceAlert;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class GeofenceAlertResource extends Resource
{
    protected static ?string $model = GeofenceAlert::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;


    protected static ?string $navigationLabel = 'Geofence Alerts';

    protected static string|UnitEnum|null $navigationGroup = 'Fleet';

    protected static ?int $navigationSort = 7;

    protected static ?string $recordTitleAttribute = 'id';

    public static function table(Table $table): Table
    {
        return GeofenceAlertsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['bike', 'booking']);
    }

    /** Badge of unresolved alerts on the nav item. */
    public static function getNavigationBadge(): ?string
    {
        $count = GeofenceAlert::where('resolved', false)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGeofenceAlerts::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('tracking.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
