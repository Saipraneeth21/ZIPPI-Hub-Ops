<?php

namespace App\Filament\Resources\IncidentReports\Tables;

use App\Filament\Resources\IncidentReports\Schemas\IncidentReportForm;
use App\Filament\Support\Filters\DateRangeFilter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class IncidentReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bike.name')
                    ->label('Bike name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('incident_type')
                    ->label('Incident type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => IncidentReportForm::types()[$state] ?? $state)
                    ->color(fn (?string $state) => match ($state) {
                        'accident', 'fire', 'theft' => 'danger',
                        'damage', 'vandalism', 'mechanical_failure', 'breakdown' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('severity')
                    ->label('Severity')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => IncidentReportForm::severities()[$state] ?? $state)
                    ->color(fn (?string $state) => match ($state) {
                        'severe', 'total_loss' => 'danger',
                        'moderate' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => IncidentReportForm::statuses()[$state] ?? $state)
                    ->color(fn (?string $state) => match ($state) {
                        'resolved', 'closed' => 'success',
                        'under_review' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('incident_date')
                    ->label('Incident date')
                    ->date('M j, Y')
                    ->sortable(),

                TextColumn::make('estimated_cost')
                    ->label('Est. cost')
                    ->money('INR', divideBy: 100)
                    ->sortable(),

                TextColumn::make('location')
                    ->label('Location')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                DateRangeFilter::make(),
                SelectFilter::make('incident_type')
                    ->label('Incident type')
                    ->options(IncidentReportForm::types()),
                SelectFilter::make('severity')
                    ->label('Severity')
                    ->options(IncidentReportForm::severities()),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(IncidentReportForm::statuses()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('incident_date', 'desc');
    }
}
