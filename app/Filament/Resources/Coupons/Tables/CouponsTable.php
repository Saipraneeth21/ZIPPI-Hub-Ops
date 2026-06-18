<?php

namespace App\Filament\Resources\Coupons\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->searchable()->copyable()->weight('medium'),
                TextColumn::make('type')->badge(),
                TextColumn::make('value')
                    ->label('Discount')
                    ->formatStateUsing(fn ($state, $record) => $record->type === 'percent'
                        ? ($state / 100) . '%'
                        : '₹' . number_format($state / 100, 2)),
                TextColumn::make('min_booking_amount')->label('Min spend')->money('INR', divideBy: 100)->placeholder('—'),
                TextColumn::make('used_count')
                    ->label('Used')
                    ->formatStateUsing(fn ($state, $record) => $record->usage_limit_total
                        ? "{$state} / {$record->usage_limit_total}"
                        : (string) $state),
                TextColumn::make('valid_to')->label('Expires')->dateTime('d M Y')->timezone('Asia/Kolkata')->sortable(),
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
            ->defaultSort('created_at', 'desc');
    }
}
