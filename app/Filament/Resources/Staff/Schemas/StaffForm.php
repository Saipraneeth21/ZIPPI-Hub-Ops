<?php

namespace App\Filament\Resources\Staff\Schemas;

use App\Enums\AdminRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StaffForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Staff login')
                ->description('Create a role-based login for a staff member. The role controls what they can access in the dashboard.')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(150),

                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->maxLength(150)
                        ->unique(ignoreRecord: true),

                    Select::make('role')
                        ->label('Role')
                        ->options(collect(AdminRole::cases())
                            ->mapWithKeys(fn (AdminRole $role) => [$role->value => $role->label()])
                            ->all())
                        ->required()
                        ->native(false)
                        ->searchable(),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->helperText('Inactive staff cannot sign in.')
                        ->default(true),

                    TextInput::make('password')
                        ->label('Password')
                        ->password()
                        ->revealable()
                        ->minLength(8)
                        // Required when creating; optional (leave blank to keep) when editing.
                        // The AdminUser model's 'hashed' cast hashes the value on save.
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->helperText(fn (string $operation): ?string => $operation === 'edit'
                            ? 'Leave blank to keep the current password.'
                            : null)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
