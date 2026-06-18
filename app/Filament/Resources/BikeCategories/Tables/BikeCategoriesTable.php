<?php

namespace App\Filament\Resources\BikeCategories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class BikeCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('medium'),
                TextColumn::make('slug')->searchable()->color('gray'),
                TextColumn::make('bikes_count')->label('Bikes')->counts('bikes')->badge(),
                TextColumn::make('default_deposit_amount')
                    ->label('Default deposit')
                    ->money('INR', divideBy: 100),
                IconColumn::make('is_active')->label('Active')->boolean()->sortable(),
                TextColumn::make('sort_order')->label('Order')->sortable(),
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
            ]);
    }
}
