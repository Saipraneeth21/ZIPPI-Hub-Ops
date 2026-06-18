<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Enums\PaymentStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Payments';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payment_reference')->label('Reference')->searchable()->copyable(),
                TextColumn::make('booking.booking_code')->label('Booking')->placeholder('—'),
                TextColumn::make('amount')->money('INR', divideBy: 100),
                TextColumn::make('method')->badge()->placeholder('—'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => match ($state) {
                        PaymentStatus::Captured->value => 'success',
                        PaymentStatus::Refunded->value => 'info',
                        PaymentStatus::Failed->value => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('paid_at')->label('Paid')->dateTime('d M Y, h:i A')->placeholder('—')->timezone('Asia/Kolkata'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25])
            // Read-only: refunds/adjustments live in the Payments module.
            ->headerActions([])
            ->recordActions([]);
    }
}
