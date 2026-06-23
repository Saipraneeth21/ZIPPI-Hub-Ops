<?php

namespace App\Filament\Resources\Hubs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HubForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Hub')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(150),
                    Select::make('city_id')
                        ->label('City')
                        ->relationship('city', 'name')
                        ->searchable()->preload()->required(),
                    TextInput::make('address')->required()->maxLength(255)->columnSpanFull(),
                    TextInput::make('latitude')->numeric()->minValue(-90)->maxValue(90)
                        ->helperText('Optional — used to plot the hub on the map.'),
                    TextInput::make('longitude')->numeric()->minValue(-180)->maxValue(180)
                        ->helperText('Optional — used to plot the hub on the map.'),
                    TimePicker::make('opening_time')->seconds(false)->default('06:00'),
                    TimePicker::make('closing_time')->seconds(false)->default('22:00'),
                    Toggle::make('is_active')->default(true)->inline(false),
                ]),
        ]);
    }
}
