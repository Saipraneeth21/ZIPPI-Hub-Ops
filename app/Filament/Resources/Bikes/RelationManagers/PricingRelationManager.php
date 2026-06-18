<?php

namespace App\Filament\Resources\Bikes\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PricingRelationManager extends RelationManager
{
    protected static string $relationship = 'prices';

    protected static ?string $title = 'Pricing';

    /** Rupees <-> paise helper for a money TextInput. */
    private static function rupees(TextInput $input): TextInput
    {
        return $input
            ->numeric()
            ->minValue(0)
            ->prefix('₹')
            ->formatStateUsing(fn (?int $state) => $state !== null ? $state / 100 : null)
            ->dehydrateStateUsing(fn (?string $state) => $state === null || $state === ''
                ? null
                : (int) round(((float) $state) * 100));
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            self::rupees(TextInput::make('hourly_rate')->label('Hourly rate (₹)')),
            self::rupees(TextInput::make('daily_rate')->label('Daily rate (₹)')),
            self::rupees(TextInput::make('weekly_rate')->label('Weekly rate (₹)')),
            self::rupees(TextInput::make('monthly_rate')->label('Monthly rate (₹)')),
            TextInput::make('min_hours')->numeric()->minValue(1)->default(1)->required(),
            DatePicker::make('effective_from'),
            DatePicker::make('effective_to')->after('effective_from'),
            Toggle::make('is_active')->default(true)->inline(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('hourly_rate')->label('Hourly')->money('INR', divideBy: 100)->placeholder('—'),
                TextColumn::make('daily_rate')->label('Daily')->money('INR', divideBy: 100)->placeholder('—'),
                TextColumn::make('weekly_rate')->label('Weekly')->money('INR', divideBy: 100)->placeholder('—'),
                TextColumn::make('monthly_rate')->label('Monthly')->money('INR', divideBy: 100)->placeholder('—'),
                TextColumn::make('min_hours')->label('Min hrs'),
                TextColumn::make('effective_from')->date('d M Y')->placeholder('—'),
                TextColumn::make('effective_to')->date('d M Y')->placeholder('—'),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('is_active', 'desc');
    }
}
