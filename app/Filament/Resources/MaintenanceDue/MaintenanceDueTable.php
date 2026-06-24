<?php

namespace App\Filament\Resources\MaintenanceDue;

use App\Enums\BikeStatus;
use App\Filament\Resources\MaintenanceRecords\MaintenanceRecordResource;
use App\Models\Rental\Bike;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MaintenanceDueTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('Bike id')->sortable(),
                TextColumn::make('name')->label('Bike')->searchable()->sortable()->weight('medium'),
                TextColumn::make('hub.name')->label('Hub')->toggleable(),
                TextColumn::make('reason')
                    ->label('Status')
                    ->badge()
                    ->state(fn (Bike $record) => self::reason($record))
                    ->color(fn (Bike $record) => match (self::reason($record)) {
                        'In maintenance' => 'info',
                        'Overdue' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('latestMaintenanceRecord.maintenance_type')->label('Last service')->placeholder('—'),
                TextColumn::make('latestMaintenanceRecord.maintenance_date')->label('Last serviced')->date('M j, Y')->placeholder('No records'),
                TextColumn::make('latestMaintenanceRecord.next_service_due')->label('Next service due')->date('M j, Y')->placeholder('—'),
                TextColumn::make('due_in')
                    ->label('Timing')
                    ->state(function (Bike $record): string {
                        $due = $record->latestMaintenanceRecord?->next_service_due;

                        if (! $due) {
                            return '—';
                        }

                        $days = (int) round(now()->startOfDay()->diffInDays($due, false));

                        if ($days < 0) {
                            return abs($days) . ' day' . (abs($days) === 1 ? '' : 's') . ' overdue';
                        }

                        return $days === 0 ? 'Due today' : 'in ' . $days . ' day' . ($days === 1 ? '' : 's');
                    }),
            ])
            ->filters([
                SelectFilter::make('cause')
                    ->label('Reason')
                    ->options([
                        'maintenance' => 'In maintenance (status)',
                        'overdue' => 'Overdue by date',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'maintenance' => $query->where('status', BikeStatus::Maintenance->value),
                            'overdue' => $query->whereHas(
                                'latestMaintenanceRecord',
                                fn (Builder $r) => $r->whereDate('next_service_due', '<', now())
                            ),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                Action::make('logService')
                    ->label('Log service')
                    ->icon('heroicon-m-wrench-screwdriver')
                    ->url(fn () => MaintenanceRecordResource::getUrl('create'))
                    ->visible(fn () => auth()->user()?->can('bikes.manage') ?? false),
            ])
            ->emptyStateIcon('heroicon-o-check-circle')
            ->emptyStateHeading('No bikes need servicing')
            ->emptyStateDescription('Bikes appear here when set to Maintenance status, or when their next service date is within ' . MaintenanceDueResource::DUE_SOON_DAYS . ' days or overdue.')
            ->defaultSort('id', 'asc');
    }

    /** Why this bike is in the worklist. */
    private static function reason(Bike $record): string
    {
        if ($record->status === BikeStatus::Maintenance->value) {
            return 'In maintenance';
        }

        $due = $record->latestMaintenanceRecord?->next_service_due;

        if ($due && $due->startOfDay()->lt(now()->startOfDay())) {
            return 'Overdue';
        }

        return 'Due soon';
    }
}
