<?php

namespace App\Filament\Resources\MaintenanceRecords\Tables;

use App\Filament\Support\Filters\DateRangeFilter;
use App\Filament\Resources\MaintenanceRecords\Schemas\MaintenanceRecordForm;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MaintenanceRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bike_id')->label('Bike id')->sortable()->searchable(),
                TextColumn::make('maintenance_type')->label('Maintenance type')->sortable()->searchable(),
                TextColumn::make('maintenance_date')->label('Maintenance date')->date('M j, Y')->sortable(),
                TextColumn::make('cost')->label('Cost')->money('INR', divideBy: 100)->sortable(),
                TextColumn::make('odometer_reading')->label('Odometer reading')->numeric()->sortable(),
                TextColumn::make('next_service_due')->label('Next service due')->date('M j, Y')->sortable(),
            ])
            ->filters([
                DateRangeFilter::make(),
                SelectFilter::make('maintenance_type')
                    ->label('Maintenance type')
                    ->options(MaintenanceRecordForm::types()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('maintenance_date', 'desc');
    }
}
