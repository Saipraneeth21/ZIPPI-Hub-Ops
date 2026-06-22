<?php

namespace App\Filament\Resources\Hubs\Tables;

use App\Filament\Support\Filters\DateRangeFilter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class HubsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('medium'),
                TextColumn::make('city.name')->label('City')->badge()->color('gray'),
                TextColumn::make('address')->limit(40)->toggleable(),
                TextColumn::make('bikes_count')->label('Bikes')->counts('bikes')->badge(),
                TextColumn::make('opening_time')->time('H:i')->label('Opens'),
                TextColumn::make('closing_time')->time('H:i')->label('Closes'),
                IconColumn::make('is_active')->label('Active')->boolean()->sortable(),
            ])
            ->filters([
                DateRangeFilter::make(),
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
            ->defaultSort('name');
    }
}
