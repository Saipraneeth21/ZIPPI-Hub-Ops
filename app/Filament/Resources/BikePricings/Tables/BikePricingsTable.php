<?php

namespace App\Filament\Resources\BikePricings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class BikePricingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bike.name')->label('Bike')->searchable()->sortable()->weight('medium'),
                TextColumn::make('bike.category.name')->label('Category')->badge()->color('gray')->toggleable(),
                TextColumn::make('hourly_rate')->label('Hourly')->money('INR', divideBy: 100)->placeholder('—'),
                TextColumn::make('daily_rate')->label('Daily')->money('INR', divideBy: 100)->placeholder('—'),
                TextColumn::make('weekly_rate')->label('Weekly')->money('INR', divideBy: 100)->placeholder('—'),
                TextColumn::make('monthly_rate')->label('Monthly')->money('INR', divideBy: 100)->placeholder('—'),
                TextColumn::make('min_hours')->label('Min hrs'),
                IconColumn::make('is_active')->label('Active')->boolean()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('bike_id');
    }
}
