<?php

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WalletTransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'walletTransactions';

    protected static ?string $title = 'Wallet Ledger';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime('d M Y, h:i A')->timezone('Asia/Kolkata'),
                TextColumn::make('direction')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => $state === 'credit' ? 'success' : 'danger'),
                TextColumn::make('amount')
                    ->money('INR', divideBy: 100)
                    ->formatStateUsing(fn ($state, $record) => ($record->direction === 'credit' ? '+' : '−') . '₹' . number_format($state / 100, 2)),
                TextColumn::make('balance_after')->label('Balance after')->money('INR', divideBy: 100),
                TextColumn::make('source_type')->label('Source')->badge()->placeholder('—'),
                TextColumn::make('description')->wrap()->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25])
            // Read-only: wallet adjustments are super_admin-only in the Payments module.
            ->headerActions([])
            ->recordActions([]);
    }
}
