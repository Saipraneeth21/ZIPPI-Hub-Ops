<?php

namespace App\Filament\Resources\CustomerDocuments\Schemas;

use App\Enums\KycStatus;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerDocumentForm
{
    /** Document types riders can submit. */
    public const TYPES = [
        'government_id' => 'Government ID',
        'driving_license' => 'Driving License',
        'aadhaar' => 'Aadhaar',
        'passport' => 'Passport',
        'pan' => 'PAN',
        'voter_id' => 'Voter ID',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Document')
                ->columns(2)
                ->schema([
                    Select::make('user_id')
                        ->label('User')
                        ->relationship('user', 'name')
                        ->getOptionLabelFromRecordUsing(fn (User $record) => "{$record->name} ({$record->email})")
                        ->searchable(['name', 'email'])
                        ->preload()
                        ->required(),

                    Select::make('document_type')
                        ->label('Document type')
                        ->options(self::TYPES)
                        ->required(),

                    TextInput::make('document_number_masked')
                        ->label('Document number')
                        ->maxLength(60)
                        ->helperText('Store a masked value — never the full number.'),

                    FileUpload::make('file_path')
                        ->label('Document file')
                        ->disk(config('filesystems.default'))
                        ->visibility('private')
                        ->directory('kyc')
                        ->openable()
                        ->downloadable()
                        ->maxSize(10240)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf', 'text/plain']),
                ]),

            Section::make('Verification')
                ->columns(2)
                ->schema([
                    Select::make('status')
                        ->label('Verification status')
                        ->options(collect(KycStatus::cases())->mapWithKeys(fn ($c) => [
                            $c->value => str($c->value)->headline(),
                        ])->all())
                        ->default(KycStatus::Pending->value)
                        ->required()
                        ->live(),

                    DateTimePicker::make('verified_at')
                        ->label('Date of verification')
                        ->native(false)
                        ->seconds(false)
                        ->timezone('Asia/Kolkata')
                        ->helperText('Auto-set when status becomes approved or rejected, if left blank.'),
                ]),
        ]);
    }
}
