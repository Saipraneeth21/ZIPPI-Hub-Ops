<?php

namespace App\Filament\Resources\BikePricings\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BikePricingForm
{
    /** Rupees <-> paise helper for a money TextInput. */
    private static function rupees(TextInput $input): TextInput
    {
        return $input
            ->numeric()
            ->minValue(0)
            ->prefix('₹')
            ->formatStateUsing(fn (?int $state) => $state !== null ? $state / 100 : null)
            ->dehydrateStateUsing(fn ($state) => $state === null || $state === ''
                ? null
                : (int) round(((float) $state) * 100));
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Plan')
                ->columns(2)
                ->schema([
                    Select::make('bike_id')
                        ->label('Bike')
                        ->relationship('bike', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('min_hours')->numeric()->minValue(1)->default(1)->required(),
                ]),

            Section::make('Rates')
                ->columns(4)
                ->schema([
                    self::rupees(TextInput::make('hourly_rate')->label('Hourly (₹)')),
                    self::rupees(TextInput::make('daily_rate')->label('Daily (₹)')),
                    self::rupees(TextInput::make('weekly_rate')->label('Weekly (₹)')),
                    self::rupees(TextInput::make('monthly_rate')->label('Monthly (₹)')),
                ]),

            Section::make('Validity')
                ->columns(3)
                ->schema([
                    DatePicker::make('effective_from'),
                    DatePicker::make('effective_to')->after('effective_from'),
                    Toggle::make('is_active')->default(true)->inline(false),
                ]),
        ]);
    }
}
