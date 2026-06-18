<?php

namespace App\Filament\Resources\BikeCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BikeCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(100)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set, $operation) => $operation === 'create'
                            ? $set('slug', Str::slug((string) $state))
                            : null),

                    TextInput::make('slug')
                        ->required()
                        ->maxLength(120)
                        ->unique(ignoreRecord: true)
                        ->helperText('URL-safe identifier; auto-filled from the name.'),

                    Textarea::make('description')
                        ->columnSpanFull()
                        ->maxLength(1000),

                    TextInput::make('default_deposit_amount')
                        ->label('Default deposit (₹)')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->prefix('₹')
                        ->helperText('Stored in paise; entered in rupees.')
                        ->formatStateUsing(fn (?int $state) => $state !== null ? $state / 100 : null)
                        ->dehydrateStateUsing(fn (?string $state) => (int) round(((float) $state) * 100)),

                    TextInput::make('sort_order')
                        ->numeric()
                        ->default(0)
                        ->required(),

                    Toggle::make('is_active')
                        ->default(true)
                        ->inline(false),
                ]),
        ]);
    }
}
