<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('User')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(150),

                    TextInput::make('email')
                        ->label('Email address')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    TextInput::make('country_code')
                        ->label('Country code')
                        ->default('+91')
                        ->required()
                        ->maxLength(6),

                    TextInput::make('mobile')
                        ->label('Mobile number')
                        ->tel()
                        ->required()
                        ->maxLength(15)
                        ->unique(ignoreRecord: true),

                    // No password field: riders authenticate via mobile OTP, not a
                    // password (users.password stays null).
                ]),
        ]);
    }
}
