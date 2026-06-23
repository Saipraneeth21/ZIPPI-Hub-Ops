<?php

namespace App\Filament\Resources\IncidentReports\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class IncidentReportForm
{
    /** Incident type options (value => label). */
    public static function types(): array
    {
        return [
            'accident' => 'Accident',
            'damage' => 'Damage',
            'theft' => 'Theft',
            'breakdown' => 'Breakdown',
            'mechanical_failure' => 'Mechanical failure',
            'vandalism' => 'Vandalism',
            'fire' => 'Fire',
            'other' => 'Other',
        ];
    }

    /** Severity options (value => label). */
    public static function severities(): array
    {
        return [
            'minor' => 'Minor',
            'moderate' => 'Moderate',
            'severe' => 'Severe',
            'total_loss' => 'Total loss',
        ];
    }

    /** Status / lifecycle options (value => label). */
    public static function statuses(): array
    {
        return [
            'open' => 'Open',
            'under_review' => 'Under review',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
        ];
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Incident report')
                ->columns(2)
                ->schema([
                    Select::make('bike_id')
                        ->label('Bike name')
                        ->relationship('bike', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('incident_type')
                        ->label('Incident type')
                        ->options(self::types())
                        ->required()
                        ->native(false),

                    Select::make('severity')
                        ->label('Severity')
                        ->options(self::severities())
                        ->default('minor')
                        ->required()
                        ->native(false),

                    Select::make('status')
                        ->label('Status')
                        ->options(self::statuses())
                        ->default('open')
                        ->required()
                        ->native(false),

                    DatePicker::make('incident_date')
                        ->label('Incident date')
                        ->required()
                        ->default(now())
                        ->maxDate(now()),

                    TextInput::make('location')
                        ->label('Location')
                        ->maxLength(255)
                        ->placeholder('Where it happened'),

                    TextInput::make('reported_by')
                        ->label('Reported by')
                        ->maxLength(255)
                        ->placeholder('Name of reporter (optional)'),

                    TextInput::make('estimated_cost')
                        ->label('Estimated repair cost')
                        ->prefix('₹')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        // Stored in paise; admin enters/sees rupees.
                        ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                        ->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),

                    Textarea::make('description')
                        ->label('Description')
                        ->required()
                        ->rows(4)
                        ->maxLength(2000)
                        ->columnSpanFull(),

                    FileUpload::make('photo_path')
                        ->label('Photo (optional)')
                        ->image()
                        ->disk(config('filesystems.default'))
                        ->directory('incidents')
                        ->helperText('Optional photo of the damage / incident.')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
