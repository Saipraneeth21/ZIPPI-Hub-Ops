<?php

namespace App\Filament\Resources\Wallets\Tables;

use App\Filament\Resources\Wallets\Support\WalletAdjustAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WalletsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('Rider')->searchable()->sortable()->weight('medium'),
                TextColumn::make('user.mobile')
                    ->label('Mobile')
                    ->searchable()
                    ->formatStateUsing(fn ($state, $record) => $record->user
                        ? $record->user->country_code . ' ' . $state
                        : '—'),
                TextColumn::make('balance')->money('INR', divideBy: 100)->sortable(),
                TextColumn::make('currency')->badge()->color('gray'),
                TextColumn::make('transactions_count')->label('Entries')->counts('transactions')->badge(),
                TextColumn::make('updated_at')->label('Updated')->since()->timezone('Asia/Kolkata')->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
                WalletAdjustAction::make(),
            ])
            ->defaultSort('balance', 'desc');
    }
}
