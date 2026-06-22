<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Filament\Support\Filters\DateRangeFilter;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Payments\Support\InitiateRefundAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentsTable
{
    public static function statusColor(string $state): string
    {
        return match ($state) {
            PaymentStatus::Captured->value => 'success',
            PaymentStatus::Authorized->value => 'info',
            PaymentStatus::Refunded->value => 'gray',
            PaymentStatus::Failed->value => 'danger',
            default => 'warning',
        };
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payment_reference')->label('Reference')->searchable()->copyable()->weight('medium'),
                TextColumn::make('booking.booking_code')->label('Booking')->searchable()->placeholder('—'),
                TextColumn::make('user.name')->label('Rider')->searchable()->toggleable(),
                TextColumn::make('amount')
                    ->money('INR', divideBy: 100)
                    ->sortable()
                    // Reconciliation: total of the filtered rows.
                    ->summarize(Sum::make()->label('Total')->money('INR', divideBy: 100)),
                TextColumn::make('method')->badge()->placeholder('—'),
                TextColumn::make('gateway')->badge()->color('gray')->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => self::statusColor($state)),
                TextColumn::make('paid_at')->label('Paid')->dateTime('d M Y, h:i A')->placeholder('—')->timezone('Asia/Kolkata')->sortable(),
            ])
            ->filters([
                DateRangeFilter::make(),
                SelectFilter::make('status')
                    ->options(collect(PaymentStatus::cases())->mapWithKeys(fn ($c) => [
                        $c->value => ucfirst($c->value),
                    ])->all()),
                SelectFilter::make('method')
                    ->options(['upi' => 'UPI', 'card' => 'Card', 'netbanking' => 'Net banking', 'wallet' => 'Wallet']),
                SelectFilter::make('gateway')
                    ->options(['razorpay' => 'Razorpay']),
                Filter::make('paid_at')
                    ->schema([
                        DatePicker::make('from')->label('Paid from'),
                        DatePicker::make('until')->label('Paid until'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('paid_at', '>=', $d))
                        ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('paid_at', '<=', $d))),
            ])
            ->recordActions([
                ViewAction::make(),
                InitiateRefundAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
