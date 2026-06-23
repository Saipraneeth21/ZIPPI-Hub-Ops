<?php

namespace App\Filament\Resources\HubStaff\Tables;

use App\Filament\Resources\HubStaff\Schemas\HubStaffForm;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class HubStaffTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo_path')
                    ->label('Photo')
                    ->disk(config('filesystems.default'))
                    ->circular(),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => HubStaffForm::roles()[$state] ?? $state)
                    ->sortable(),

                TextColumn::make('employee_code')
                    ->label('Employee code')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('hub.name')
                    ->label('Hub')
                    ->placeholder('—')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Role')
                    ->options(HubStaffForm::roles()),

                SelectFilter::make('hub_id')
                    ->label('Hub')
                    ->relationship('hub', 'name'),

                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
