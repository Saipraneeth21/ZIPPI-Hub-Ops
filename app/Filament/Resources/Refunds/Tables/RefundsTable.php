<?php

namespace App\Filament\Resources\Refunds\Tables;

use App\Filament\Support\Filters\DateRangeFilter;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RefundsTable
{
    public static function statusColor(string $state): string
    {
        return match ($state) {
            'processed' => 'success',
            'failed' => 'danger',
            default => 'warning',
        };
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('refund_reference')->label('Reference')->searchable()->copyable()->weight('medium'),
                TextColumn::make('booking.booking_code')->label('Booking')->searchable()->placeholder('—'),
                TextColumn::make('user.name')->label('Rider')->searchable()->toggleable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('amount')
                    ->label('Net')
                    ->money('INR', divideBy: 100)
                    ->sortable()
                    ->summarize(Sum::make()->label('Total')->money('INR', divideBy: 100)),
                TextColumn::make('deductions')->money('INR', divideBy: 100)->toggleable(),
                TextColumn::make('destination')->badge()->color('gray'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => self::statusColor($state)),
                TextColumn::make('processed_at')->label('Processed')->dateTime('d M Y, h:i A')->placeholder('—')->timezone('Asia/Kolkata')->sortable(),
            ])
            ->filters([
                DateRangeFilter::make(),
                SelectFilter::make('status')
                    ->options(['pending' => 'Pending', 'processed' => 'Processed', 'failed' => 'Failed']),
                SelectFilter::make('type')
                    ->options(['deposit' => 'Deposit', 'cancellation' => 'Cancellation', 'partial' => 'Partial']),
                SelectFilter::make('destination')
                    ->options(['wallet' => 'Wallet', 'source' => 'Source']),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
