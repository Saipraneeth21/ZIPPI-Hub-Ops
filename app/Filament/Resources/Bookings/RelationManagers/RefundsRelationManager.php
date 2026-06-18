<?php

namespace App\Filament\Resources\Bookings\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RefundsRelationManager extends RelationManager
{
    protected static string $relationship = 'refunds';

    protected static ?string $title = 'Refunds';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('refund_reference')->label('Reference')->copyable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('amount')->label('Net')->money('INR', divideBy: 100),
                TextColumn::make('deductions')->money('INR', divideBy: 100),
                TextColumn::make('deduction_note')->label('Note')->placeholder('—')->wrap(),
                TextColumn::make('destination')->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => match ($state) {
                        'processed' => 'success',
                        'failed' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('processed_at')->label('Processed')->dateTime('d M Y, h:i A')->placeholder('—')->timezone('Asia/Kolkata'),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([])
            ->recordActions([]);
    }
}
