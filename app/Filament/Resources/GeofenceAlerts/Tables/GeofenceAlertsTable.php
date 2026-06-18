<?php

namespace App\Filament\Resources\GeofenceAlerts\Tables;

use App\Models\Rental\GeofenceAlert;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class GeofenceAlertsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime('d M Y, h:i A')->timezone('Asia/Kolkata')->sortable(),
                TextColumn::make('bike.name')->label('Bike')->searchable()->placeholder('—'),
                TextColumn::make('booking.booking_code')->label('Booking')->placeholder('—'),
                TextColumn::make('severity')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => match ($state) {
                        'high' => 'danger',
                        'medium' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('latitude')
                    ->label('Location')
                    ->formatStateUsing(fn ($state, $record) => number_format($record->latitude, 5) . ', ' . number_format($record->longitude, 5)),
                IconColumn::make('resolved')->boolean()->sortable(),
            ])
            ->filters([
                SelectFilter::make('severity')
                    ->options(['high' => 'High', 'medium' => 'Medium', 'low' => 'Low']),
                TernaryFilter::make('resolved')
                    ->label('Resolved')
                    ->default(false),
            ])
            ->recordActions([
                Action::make('resolve')
                    ->label('Resolve')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->visible(fn (GeofenceAlert $record) => ! $record->resolved
                        && (auth()->user()?->can('tracking.manage') ?? false))
                    ->requiresConfirmation()
                    ->action(function (GeofenceAlert $record): void {
                        $record->update(['resolved' => true]);
                        Notification::make()->title('Alert resolved')->success()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
