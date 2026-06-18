<?php

namespace App\Filament\Resources\InstantDispatches\Schemas;

use App\Enums\DurationType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InstantDispatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Customer details')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(150),

                    TextInput::make('mobile')
                        ->label('Mobile number')
                        ->tel()
                        ->required()
                        ->minLength(10)
                        ->maxLength(15)
                        ->rule('regex:/^[0-9+\-\s]+$/'),

                    TextInput::make('aadhar_number')
                        ->label('Aadhaar number')
                        ->required()
                        ->mask('9999 9999 9999')
                        ->rule('regex:/^\d{4}\s?\d{4}\s?\d{4}$/')
                        ->helperText('12-digit Aadhaar. Stored encrypted.')
                        ->dehydrateStateUsing(fn (?string $state) => preg_replace('/\s+/', '', (string) $state)),

                    TextInput::make('driving_license')
                        ->label('Driving license')
                        ->required()
                        ->maxLength(40),
                ]),

            Section::make('Rental')
                ->columns(2)
                ->schema([
                    DatePicker::make('pickup_date')
                        ->label('Pick-up date')
                        ->required()
                        ->native(false)
                        ->minDate(now()->startOfDay()),

                    Select::make('rental_type')
                        ->label('Type of rental')
                        ->options(collect(DurationType::cases())->mapWithKeys(fn ($c) => [
                            $c->value => ucfirst($c->value),
                        ])->all())
                        ->default(DurationType::Daily->value)
                        ->required(),

                    Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'dispatched' => 'Dispatched',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('pending')
                        ->required(),
                ]),
        ]);
    }
}
