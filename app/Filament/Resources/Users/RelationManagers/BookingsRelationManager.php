<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Enums\BookingStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';

    protected static ?string $title = 'Bookings';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking_code')->label('Code')->searchable(),
                TextColumn::make('bike.name')->label('Bike')->placeholder('—'),
                TextColumn::make('duration_type')->label('Type')->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => match ($state) {
                        BookingStatus::Completed->value => 'success',
                        BookingStatus::Active->value, BookingStatus::Confirmed->value => 'warning',
                        BookingStatus::Cancelled->value, BookingStatus::Expired->value => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('total_amount')->label('Total')->money('INR', divideBy: 100),
                TextColumn::make('start_at')->label('Start')->dateTime('d M Y, h:i A')->timezone('Asia/Kolkata'),
                TextColumn::make('created_at')->label('Booked')->dateTime('d M Y')->timezone('Asia/Kolkata'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25])
            // Read-only: bookings are operated from the Orders module.
            ->headerActions([])
            ->recordActions([]);
    }
}
