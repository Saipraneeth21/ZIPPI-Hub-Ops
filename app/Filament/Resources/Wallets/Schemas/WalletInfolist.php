<?php

namespace App\Filament\Resources\Wallets\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WalletInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Wallet')
                ->columns(3)
                ->schema([
                    TextEntry::make('user.name')->label('Rider'),
                    TextEntry::make('user.mobile')
                        ->label('Mobile')
                        ->formatStateUsing(fn ($state, $record) => $record->user
                            ? $record->user->country_code . ' ' . $state
                            : '—'),
                    TextEntry::make('balance')->money('INR', divideBy: 100)->weight('bold'),
                    TextEntry::make('currency')->badge()->color('gray'),
                    TextEntry::make('updated_at')->label('Last updated')->dateTime('d M Y, h:i A')->timezone('Asia/Kolkata'),
                ]),
        ]);
    }
}
