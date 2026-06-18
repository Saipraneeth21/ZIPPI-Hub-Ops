<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Filament\Resources\Payments\Tables\PaymentsTable;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Payment')
                ->columns(3)
                ->schema([
                    TextEntry::make('payment_reference')->label('Reference')->copyable(),
                    TextEntry::make('amount')->money('INR', divideBy: 100)->weight('bold'),
                    TextEntry::make('status')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => ucfirst($state))
                        ->color(fn (string $state) => PaymentsTable::statusColor($state)),
                    TextEntry::make('method')->badge()->placeholder('—'),
                    TextEntry::make('currency'),
                    TextEntry::make('paid_at')->label('Paid at')->dateTime('d M Y, h:i A')->placeholder('—')->timezone('Asia/Kolkata'),
                    TextEntry::make('user.name')->label('Rider'),
                    TextEntry::make('booking.booking_code')->label('Booking')->placeholder('—'),
                    TextEntry::make('failure_reason')->label('Failure reason')->placeholder('—'),
                ]),

            Section::make('Gateway references')
                ->columns(2)
                ->schema([
                    TextEntry::make('gateway')->badge()->color('gray'),
                    TextEntry::make('gateway_order_id')->label('Order ID')->copyable()->placeholder('—'),
                    TextEntry::make('gateway_payment_id')->label('Payment ID')->copyable()->placeholder('—'),
                    TextEntry::make('idempotency_key')->label('Idempotency key')->copyable()->placeholder('—'),
                ]),
        ]);
    }
}
