<?php

namespace App\Filament\Resources\Reviews\Tables;

use App\Filament\Resources\Reviews\Support\ModerateReviewAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('rating')
                    ->badge()
                    ->formatStateUsing(fn (int $state) => str_repeat('★', $state) . str_repeat('☆', 5 - $state))
                    ->color(fn (int $state) => $state >= 4 ? 'success' : ($state >= 3 ? 'warning' : 'danger'))
                    ->sortable(),
                TextColumn::make('comment')->limit(60)->wrap()->placeholder('—'),
                TextColumn::make('user.name')->label('Rider')->searchable()->toggleable(),
                TextColumn::make('bike.name')->label('Bike')->searchable()->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => $state === 'published' ? 'success' : 'gray'),
                TextColumn::make('created_at')->label('When')->dateTime('d M Y')->timezone('Asia/Kolkata')->sortable(),
            ])
            ->filters([
                SelectFilter::make('rating')
                    ->options([5 => '5 ★', 4 => '4 ★', 3 => '3 ★', 2 => '2 ★', 1 => '1 ★']),
                SelectFilter::make('status')
                    ->options(['published' => 'Published', 'hidden' => 'Hidden']),
                SelectFilter::make('bike')->relationship('bike', 'name')->searchable()->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
                ModerateReviewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
