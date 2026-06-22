<?php

namespace App\Filament\Resources\AdminLoginActivities\Tables;

use App\Filament\Support\Filters\DateRangeFilter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AdminLoginActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime('d M Y, h:i A')->timezone('Asia/Kolkata')->sortable(),
                TextColumn::make('admin.name')->label('Admin')->placeholder('—')->searchable(),
                TextColumn::make('email')->searchable()->copyable()->placeholder('—'),
                TextColumn::make('event')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => match ($state) {
                        'login' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('ip_address')->label('IP')->searchable()->placeholder('—'),
                TextColumn::make('user_agent')->label('Device')->limit(40)->toggleable()->placeholder('—'),
            ])
            ->filters([
                DateRangeFilter::make(),
                SelectFilter::make('event')
                    ->options(['login' => 'Login', 'logout' => 'Logout', 'failed' => 'Failed']),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
