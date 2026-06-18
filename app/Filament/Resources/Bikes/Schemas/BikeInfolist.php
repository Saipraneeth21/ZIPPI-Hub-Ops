<?php

namespace App\Filament\Resources\Bikes\Schemas;

use App\Enums\BikeStatus;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BikeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Bike')
                ->columns(3)
                ->schema([
                    TextEntry::make('name'),
                    TextEntry::make('registration_no')->label('Registration no.'),
                    TextEntry::make('iot_device_id')->label('IoT device')->placeholder('—'),
                    TextEntry::make('category.name')->label('Category')->badge()->color('gray'),
                    TextEntry::make('hub.name')->label('Hub'),
                    TextEntry::make('city.name')->label('City'),
                    TextEntry::make('status')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => ucfirst($state))
                        ->color(fn (string $state) => match ($state) {
                            BikeStatus::Available->value => 'success',
                            BikeStatus::Booked->value => 'warning',
                            BikeStatus::Maintenance->value => 'info',
                            default => 'gray',
                        }),
                    TextEntry::make('fuel_type')->formatStateUsing(fn ($s) => ucfirst($s)),
                    TextEntry::make('security_deposit_amount')->label('Deposit')->money('INR', divideBy: 100),
                ]),

            Section::make('Specifications')
                ->schema([
                    KeyValueEntry::make('specifications')->hiddenLabel(),
                ]),
        ]);
    }
}
