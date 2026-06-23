<?php

namespace App\Filament\Resources\HubStaff\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HubStaffForm
{
    /** Hub staff role options (value => label). */
    public static function roles(): array
    {
        return [
            'hub_manager' => 'Hub Manager',
            'supervisor' => 'Supervisor',
            'mechanic' => 'Mechanic',
            'electrician' => 'Electrician',
            'attendant' => 'Attendant',
            'security' => 'Security Guard',
            'cleaner' => 'Cleaner',
            'other' => 'Other',
        ];
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Hub staff login')
                ->description('Create a login for an on-site hub staff member.')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Name of the staff')
                        ->required()
                        ->maxLength(150),

                    Select::make('role')
                        ->label('Role of the staff')
                        ->options(self::roles())
                        ->required()
                        ->native(false)
                        ->searchable(),

                    TextInput::make('employee_code')
                        ->label('Employee code')
                        ->required()
                        ->maxLength(50)
                        ->unique(ignoreRecord: true)
                        ->placeholder('e.g. EMP-1024'),

                    Select::make('hub_id')
                        ->label('Hub')
                        ->relationship('hub', 'name')
                        ->searchable()
                        ->preload()
                        ->placeholder('Assign to a hub (optional)'),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->helperText('Inactive staff cannot sign in.')
                        ->default(true),

                    TextInput::make('password')
                        ->label('Password')
                        ->password()
                        ->revealable()
                        ->minLength(8)
                        // Required on create; leave blank on edit to keep the
                        // current password. The model's 'hashed' cast hashes it.
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->helperText(fn (string $operation): ?string => $operation === 'edit'
                            ? 'Leave blank to keep the current password.'
                            : null)
                        ->columnSpanFull(),

                    FileUpload::make('photo_path')
                        ->label('Photo')
                        ->image()
                        ->avatar()
                        ->disk(config('filesystems.default'))
                        ->directory('hub-staff')
                        ->required()
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
