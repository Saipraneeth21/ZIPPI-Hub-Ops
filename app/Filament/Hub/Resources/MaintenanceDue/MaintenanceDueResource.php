<?php

namespace App\Filament\Hub\Resources\MaintenanceDue;

use App\Filament\Hub\Resources\MaintenanceDue\Pages\ListMaintenanceDue;
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

/**
 * Hub worklist of bikes needing service: in Maintenance status, or whose next
 * service is overdue / due within 14 days. Scoped to the staff member's hub.
 * Mirrors the admin "Maintenance Due" list; reuses Bike::maintenanceDue().
 */
class MaintenanceDueResource extends Resource
{
    protected static ?string $model = Bike::class;

    protected static ?string $slug = 'maintenance-due';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $navigationLabel = 'Maintenance Due';

    protected static ?string $pluralModelLabel = 'Maintenance Due';

    protected static string|UnitEnum|null $navigationGroup = 'Fleet';

    protected static ?int $navigationSort = 2;

    public const DUE_SOON_DAYS = 14;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('hub_id', auth('hub')->user()?->hub_id)
            ->maintenanceDue(self::DUE_SOON_DAYS)
            ->with(['category', 'latestMaintenanceRecord']);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /** Why this bike is in the worklist. */
    public static function reason(Bike $bike): string
    {
        if ($bike->status === 'maintenance') {
            return 'In maintenance';
        }
        $due = $bike->latestMaintenanceRecord?->next_service_due;

        return ($due && $due->startOfDay()->lt(now()->startOfDay())) ? 'Overdue' : 'Due soon';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('registration_no')->label('Vehicle number')->searchable()->weight('medium'),
                TextColumn::make('name')->label('Bike')->searchable(),
                TextColumn::make('category.name')->label('Category')->badge()->toggleable(),
                TextColumn::make('reason')
                    ->label('Reason')
                    ->badge()
                    ->state(fn (Bike $record) => self::reason($record))
                    ->color(fn (Bike $record) => match (self::reason($record)) {
                        'In maintenance' => 'danger',
                        'Overdue' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('latestMaintenanceRecord.maintenance_type')->label('Last service')->placeholder('—'),
                TextColumn::make('latestMaintenanceRecord.next_service_due')
                    ->label('Next service due')->date('d M Y')->placeholder('—'),
                TextColumn::make('due_in')
                    ->label('Timing')
                    ->state(function (Bike $record): string {
                        $due = $record->latestMaintenanceRecord?->next_service_due;
                        if (! $due) {
                            return '—';
                        }
                        $days = (int) round(now()->startOfDay()->diffInDays($due, false));
                        if ($days < 0) {
                            return abs($days).' day'.(abs($days) === 1 ? '' : 's').' overdue';
                        }

                        return $days === 0 ? 'Due today' : 'in '.$days.' day'.($days === 1 ? '' : 's');
                    }),
            ])
            ->filters([
                SelectFilter::make('cause')
                    ->label('Reason')
                    ->options([
                        'maintenance' => 'In maintenance (status)',
                        'overdue' => 'Overdue by date',
                    ])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'maintenance' => $query->where('status', 'maintenance'),
                        'overdue' => $query->whereHas(
                            'latestMaintenanceRecord',
                            fn (Builder $r) => $r->whereDate('next_service_due', '<', now())
                        ),
                        default => $query,
                    }),
            ])
            ->recordActions([
                HubActions::reportMaintenance(),
            ])
            ->emptyStateIcon('heroicon-o-check-circle')
            ->emptyStateHeading('No bikes need servicing')
            ->emptyStateDescription('Bikes appear here when set to Maintenance status, or when their next service is within '.self::DUE_SOON_DAYS.' days or overdue.')
            ->defaultSort('registration_no', 'asc');
    }

    public static function getPages(): array
    {
        return ['index' => ListMaintenanceDue::route('/')];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
