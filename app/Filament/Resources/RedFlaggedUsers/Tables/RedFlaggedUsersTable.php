<?php

namespace App\Filament\Resources\RedFlaggedUsers\Tables;

use App\Filament\Resources\Users\Support\RedFlagToggleAction;
use App\Filament\Resources\Users\UserResource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RedFlaggedUsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('User name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    // Click the name to open the rider's profile.
                    ->url(fn ($record): string => UserResource::getUrl('view', ['record' => $record]))
                    ->color('primary'),

                TextColumn::make('mobile')
                    ->label('Number')
                    ->searchable()
                    ->formatStateUsing(fn ($state, $record) => trim(($record->country_code ?? '') . ' ' . $state)),

                TextColumn::make('profile.red_flag_reason')
                    ->label('Reason')
                    ->placeholder('—')
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('profile.red_flagged_at')
                    ->label('Flagged on')
                    ->dateTime('d M Y, h:i A')
                    ->timezone('Asia/Kolkata')
                    ->sortable(),
            ])
            ->recordActions([
                // Reused from the Users list — here it always reads "Remove flag".
                RedFlagToggleAction::make(),
            ])
            ->defaultSort('name');
    }
}
