<?php

namespace App\Filament\Resources\Bookings\RelationManagers;

use App\Filament\Resources\Bookings\Tables\BookingsTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StatusHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'statusHistory';

    protected static ?string $title = 'Status History';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime('d M Y, h:i A')->timezone('Asia/Kolkata'),
                TextColumn::make('from_status')
                    ->label('From')
                    ->badge()
                    ->placeholder('—')
                    ->formatStateUsing(fn (?string $state) => $state ? ucfirst($state) : null)
                    ->color(fn (?string $state) => $state ? BookingsTable::statusColor($state) : 'gray'),
                TextColumn::make('to_status')
                    ->label('To')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => BookingsTable::statusColor($state)),
                TextColumn::make('changed_by')->label('By admin #')->placeholder('System'),
                TextColumn::make('note')->wrap()->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([])
            ->recordActions([]);
    }
}
