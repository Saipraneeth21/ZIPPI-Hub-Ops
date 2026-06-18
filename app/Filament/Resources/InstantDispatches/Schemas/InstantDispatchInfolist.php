<?php

namespace App\Filament\Resources\InstantDispatches\Schemas;

use App\Enums\DurationType;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InstantDispatchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Customer')
                ->columns(2)
                ->schema([
                    TextEntry::make('name'),
                    TextEntry::make('mobile')->label('Mobile number'),
                    TextEntry::make('aadhar_masked')->label('Aadhaar number')->placeholder('—'),
                    TextEntry::make('driving_license')->label('Driving license'),
                ]),

            Section::make('Rental')
                ->columns(3)
                ->schema([
                    TextEntry::make('pickup_date')->label('Pick-up date')->date('d M Y'),
                    TextEntry::make('rental_type')
                        ->label('Type of rental')
                        ->badge()
                        ->formatStateUsing(fn ($state) => ucfirst($state instanceof DurationType ? $state->value : $state)),
                    TextEntry::make('status')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => ucfirst($state))
                        ->color(fn (string $state) => match ($state) {
                            'dispatched' => 'success',
                            'cancelled' => 'danger',
                            default => 'warning',
                        }),
                ]),
        ]);
    }
}
