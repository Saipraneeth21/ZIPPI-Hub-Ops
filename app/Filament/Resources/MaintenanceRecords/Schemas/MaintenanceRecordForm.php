<?php

namespace App\Filament\Resources\MaintenanceRecords\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MaintenanceRecordForm
{
    /** Maintenance type options (value => label). */
    public static function types(): array
    {
        return [
            'regular_service' => 'Regular service',
            'battery_service' => 'Battery service',
            'oil_change' => 'Oil change',
            'tyre_change' => 'Tyre change',
            'brake_service' => 'Brake service',
            'general_repair' => 'General repair',
            'inspection' => 'Inspection',
            'other' => 'Other',
        ];
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Maintenance record')
                ->columns(2)
                ->schema([
                    Select::make('bike_id')
                        ->label('Bike')
                        ->relationship('bike', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('maintenance_type')
                        ->label('Maintenance type')
                        ->options(self::types())
                        ->required()
                        ->native(false),

                    DatePicker::make('maintenance_date')
                        ->label('Maintenance date')
                        ->required()
                        ->default(now()),

                    TextInput::make('cost')
                        ->label('Cost')
                        ->prefix('₹')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        // Stored in paise; admin enters/sees rupees.
                        ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                        ->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),

                    TextInput::make('odometer_reading')
                        ->label('Odometer reading')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('km'),

                    DatePicker::make('next_service_due')
                        ->label('Next service due')
                        ->after('maintenance_date'),

                    Textarea::make('job_sheet')
                        ->label('Job sheet')
                        ->required()
                        ->rows(4)
                        ->maxLength(5000)
                        ->placeholder('Work performed, parts replaced, labour notes…')
                        ->columnSpanFull(),

                    FileUpload::make('attachments')
                        ->label('Attachments')
                        ->required()
                        ->multiple()
                        ->reorderable()
                        ->openable()
                        ->downloadable()
                        ->disk(config('filesystems.default'))
                        ->directory('maintenance')
                        ->helperText('Upload the signed job sheet, receipts, or photos.')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
