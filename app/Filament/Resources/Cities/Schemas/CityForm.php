<?php

namespace App\Filament\Resources\Cities\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('City / Location')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(120)
                        ->placeholder('e.g. Hitech City'),

                    TextInput::make('state')
                        ->label('State')
                        ->maxLength(80)
                        ->default('TS'),

                    TextInput::make('country')
                        ->label('Country')
                        ->maxLength(80)
                        ->default('IN'),

                    TextInput::make('timezone')
                        ->label('Timezone')
                        ->maxLength(40)
                        ->default('Asia/Kolkata'),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ]),
        ]);
    }
}
