<?php

namespace App\Filament\Resources\Bikes\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BikeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            // Mandatory bike photo. Saved on the Edit screen via the Images
            // relation manager, so this field is create-only; CreateBike turns
            // the uploaded file into the bike's primary image.
            Section::make('Photo')
                ->visibleOn('create')
                ->schema([
                    FileUpload::make('primary_image')
                        ->label('Bike picture')
                        ->image()
                        ->disk(config('filesystems.default'))
                        ->directory('bikes')
                        ->required()
                        ->helperText('Required. Used as the primary photo for this bike.')
                        ->columnSpanFull(),
                ]),

            Section::make('Identity')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(150),
                    TextInput::make('registration_no')
                        ->label('Registration no.')
                        ->required()
                        ->maxLength(20)
                        ->unique(ignoreRecord: true),
                    TextInput::make('iot_device_id')
                        ->label('IoT device ID')
                        ->maxLength(80)
                        ->unique(ignoreRecord: true)
                        ->placeholder('Optional'),
                    Select::make('category_id')
                        ->label('Category')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                ]),

            Section::make('Placement')
                ->columns(2)
                ->schema([
                    Select::make('hub_id')
                        ->label('Home hub')
                        ->relationship('hub', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('city_id')
                        ->label('City')
                        ->relationship('city', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                ]),

            Section::make('Specs & Deposit')
                ->columns(3)
                ->schema([
                    Select::make('fuel_type')
                        ->options(['petrol' => 'Petrol', 'electric' => 'Electric'])
                        ->default('petrol')
                        ->required(),
                    TextInput::make('year')->numeric()->minValue(2000)->maxValue(2100),
                    TextInput::make('color')->maxLength(40),
                    TextInput::make('security_deposit_amount')
                        ->label('Security deposit (₹)')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->prefix('₹')
                        ->helperText('Stored in paise; entered in rupees.')
                        ->formatStateUsing(fn (?int $state) => $state !== null ? $state / 100 : null)
                        ->dehydrateStateUsing(fn (?string $state) => (int) round(((float) $state) * 100))
                        ->columnSpan(1),
                    KeyValue::make('specifications')
                        ->keyLabel('Spec')
                        ->valueLabel('Value')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
