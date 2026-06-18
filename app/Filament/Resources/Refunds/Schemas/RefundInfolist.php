<?php

namespace App\Filament\Resources\Refunds\Schemas;

use App\Filament\Resources\Refunds\Tables\RefundsTable;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RefundInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Refund')
                ->columns(3)
                ->schema([
                    TextEntry::make('refund_reference')->label('Reference')->copyable(),
                    TextEntry::make('type')->badge(),
                    TextEntry::make('status')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => ucfirst($state))
                        ->color(fn (string $state) => RefundsTable::statusColor($state)),
                    TextEntry::make('amount')->label('Net amount')->money('INR', divideBy: 100)->weight('bold'),
                    TextEntry::make('deductions')->money('INR', divideBy: 100),
                    TextEntry::make('destination')->badge()->color('gray'),
                    TextEntry::make('deduction_note')->label('Deduction note')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('user.name')->label('Rider'),
                    TextEntry::make('booking.booking_code')->label('Booking')->placeholder('—'),
                    TextEntry::make('processed_at')->label('Processed at')->dateTime('d M Y, h:i A')->placeholder('—')->timezone('Asia/Kolkata'),
                ]),

            Section::make('References')
                ->columns(2)
                ->schema([
                    TextEntry::make('gateway_refund_id')->label('Gateway refund ID')->copyable()->placeholder('—'),
                    TextEntry::make('idempotency_key')->label('Idempotency key')->copyable()->placeholder('—'),
                ]),
        ]);
    }
}
