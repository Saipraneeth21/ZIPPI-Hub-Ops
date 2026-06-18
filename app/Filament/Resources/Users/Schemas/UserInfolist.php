<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\KycStatus;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Profile')
                ->columns(3)
                ->schema([
                    TextEntry::make('name'),
                    TextEntry::make('mobile')
                        ->formatStateUsing(fn ($state, $record) => $record->country_code . ' ' . $state),
                    TextEntry::make('email')->placeholder('—'),
                    TextEntry::make('created_at')
                        ->label('Joined')
                        ->dateTime('d M Y, h:i A')
                        ->timezone('Asia/Kolkata'),
                    TextEntry::make('email_verified_at')
                        ->label('Email verified')
                        ->dateTime('d M Y')
                        ->placeholder('Not verified')
                        ->timezone('Asia/Kolkata'),
                ]),

            Section::make('KYC & Status')
                ->columns(3)
                ->schema([
                    TextEntry::make('profile.kyc_status')
                        ->label('KYC status')
                        ->badge()
                        ->formatStateUsing(fn (?string $state) => ucwords(str_replace('_', ' ', $state ?? 'none')))
                        ->color(fn (?string $state) => match ($state) {
                            KycStatus::Approved->value => 'success',
                            KycStatus::Rejected->value => 'danger',
                            KycStatus::Pending->value, KycStatus::UnderReview->value => 'warning',
                            default => 'gray',
                        }),
                    IconEntry::make('profile.is_blocked')
                        ->label('Blocked')
                        ->boolean()
                        ->trueColor('danger')
                        ->falseColor('success'),
                    TextEntry::make('profile.blocked_reason')
                        ->label('Block reason')
                        ->placeholder('—'),
                ]),

            Section::make('Wallet & Activity')
                ->columns(3)
                ->schema([
                    TextEntry::make('wallet.balance')
                        ->label('Wallet balance')
                        ->money('INR', divideBy: 100)
                        ->placeholder('₹0.00'),
                    TextEntry::make('bookings_count')
                        ->label('Total bookings')
                        ->state(fn ($record) => $record->bookings()->count()),
                    TextEntry::make('payments_count')
                        ->label('Total payments')
                        ->state(fn ($record) => $record->payments()->count()),
                ]),
        ]);
    }
}
