<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Filament\Resources\Bookings\Tables\BookingsTable;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Booking')
                ->columns(4)
                ->schema([
                    TextEntry::make('booking_code')->label('Code')->copyable(),
                    TextEntry::make('status')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => ucfirst($state))
                        ->color(fn (string $state) => BookingsTable::statusColor($state)),
                    TextEntry::make('duration_type')->label('Type')->badge(),
                    TextEntry::make('cancellation_reason')->label('Cancellation reason')->placeholder('—'),
                    TextEntry::make('user.name')->label('Rider'),
                    TextEntry::make('user.mobile')->label('Mobile')
                        ->formatStateUsing(fn ($state, $record) => $record->user
                            ? $record->user->country_code . ' ' . $state
                            : '—'),
                    TextEntry::make('bike.name')->label('Bike'),
                    TextEntry::make('bike.registration_no')->label('Reg. no')->placeholder('—'),
                ]),

            Section::make('Timing & Hubs')
                ->columns(4)
                ->schema([
                    TextEntry::make('start_at')->label('Scheduled start')->dateTime('d M Y, h:i A')->timezone('Asia/Kolkata'),
                    TextEntry::make('end_at')->label('Scheduled end')->dateTime('d M Y, h:i A')->timezone('Asia/Kolkata'),
                    TextEntry::make('actual_start_at')->label('Actual start')->dateTime('d M Y, h:i A')->placeholder('—')->timezone('Asia/Kolkata'),
                    TextEntry::make('actual_end_at')->label('Actual end')->dateTime('d M Y, h:i A')->placeholder('—')->timezone('Asia/Kolkata'),
                    TextEntry::make('pickupHub.name')->label('Pickup hub')->placeholder('—'),
                    TextEntry::make('returnHub.name')->label('Return hub')->placeholder('—'),
                ]),

            Section::make('Amounts')
                ->columns(4)
                ->schema([
                    TextEntry::make('base_amount')->label('Base')->money('INR', divideBy: 100),
                    TextEntry::make('tax_amount')->label('Tax')->money('INR', divideBy: 100),
                    TextEntry::make('platform_fee')->label('Platform fee')->money('INR', divideBy: 100),
                    TextEntry::make('discount_amount')->label('Discount')->money('INR', divideBy: 100),
                    TextEntry::make('deposit_amount')->label('Deposit')->money('INR', divideBy: 100),
                    TextEntry::make('late_penalty')->label('Late penalty')->money('INR', divideBy: 100),
                    TextEntry::make('total_amount')->label('Total')->money('INR', divideBy: 100)->weight('bold'),
                ]),

            Section::make('Telemetry')
                ->schema([
                    TextEntry::make('telemetry_link')
                        ->hiddenLabel()
                        ->state('Open live tracking ↗')
                        ->color('primary')
                        ->url(fn ($record) => '/admin/tracking?booking=' . $record->id)
                        ->visible(fn ($record) => $record->status === 'active'),
                    TextEntry::make('telemetry_placeholder')
                        ->hiddenLabel()
                        ->state('Live tracking available while the rental is active.')
                        ->color('gray')
                        ->visible(fn ($record) => $record->status !== 'active'),
                ]),
        ]);
    }
}
