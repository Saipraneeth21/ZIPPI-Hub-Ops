<?php

namespace App\Filament\Resources\InstantDispatches\Tables;

use App\Enums\DurationType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InstantDispatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('medium'),
                TextColumn::make('mobile')->searchable(),
                TextColumn::make('aadhar_masked')->label('Aadhaar')->placeholder('—'),
                TextColumn::make('driving_license')->label('License')->searchable()->toggleable(),
                TextColumn::make('rental_type')
                    ->label('Rental')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state instanceof DurationType ? $state->value : $state)),
                TextColumn::make('pickup_date')->label('Pick-up')->date('d M Y')->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => match ($state) {
                        'dispatched' => 'success',
                        'cancelled' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('created_at')->label('Logged')->dateTime('d M Y, h:i A')->timezone('Asia/Kolkata')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(['pending' => 'Pending', 'dispatched' => 'Dispatched', 'cancelled' => 'Cancelled']),
                SelectFilter::make('rental_type')
                    ->label('Rental type')
                    ->options(collect(DurationType::cases())->mapWithKeys(fn ($c) => [
                        $c->value => ucfirst($c->value),
                    ])->all()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
