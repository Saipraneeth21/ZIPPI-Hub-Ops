<?php

namespace App\Filament\Resources\MaintenanceDue;

use App\Enums\BikeStatus;
use App\Filament\Resources\MaintenanceDue\Pages\ListMaintenanceDue;
use App\Models\Rental\Bike;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Read-only "Maintenance Due" worklist.
 *
 * A bike needs attention — and so appears here — when either:
 *   1. it has been manually set to Status = Maintenance (Fleet → Bikes → Status), or
 *   2. its latest maintenance record's `next_service_due` date is overdue or
 *      coming up within the next 14 days.
 *
 * Staff log the actual service from the Maintenance Records section; this is
 * just the list that tells them which bikes need looking at.
 */
class MaintenanceDueResource extends Resource
{
    protected static ?string $model = Bike::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?string $navigationLabel = 'Maintenance Due';

    protected static ?string $modelLabel = 'bike due for service';

    protected static ?string $pluralModelLabel = 'Maintenance Due';

    protected static string|UnitEnum|null $navigationGroup = 'Fleet';

    protected static ?int $navigationSort = 6;

    /** How many days ahead counts as "due soon". */
    public const DUE_SOON_DAYS = 14;

    /**
     * Bikes that need attention: in Maintenance status, or with a latest
     * maintenance record whose next service is overdue / due soon.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(function (Builder $query) {
                $query
                    ->where('status', BikeStatus::Maintenance->value)
                    ->orWhereHas('latestMaintenanceRecord', function (Builder $record) {
                        $record
                            ->whereNotNull('next_service_due')
                            ->whereDate('next_service_due', '<=', now()->addDays(self::DUE_SOON_DAYS));
                    });
            })
            ->with(['latestMaintenanceRecord', 'hub']);
    }

    public static function table(Table $table): Table
    {
        return MaintenanceDueTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaintenanceDue::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('bikes.manage') ?? false;
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
