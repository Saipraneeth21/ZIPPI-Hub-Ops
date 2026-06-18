<?php

namespace App\Filament\Resources\Bookings\Tables;

use App\Enums\BookingStatus;
use App\Filament\Resources\Bookings\Support\BookingActions;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingsTable
{
    public static function statusColor(string $state): string
    {
        return match ($state) {
            BookingStatus::Completed->value => 'success',
            BookingStatus::Active->value, BookingStatus::Confirmed->value => 'warning',
            BookingStatus::Cancelled->value, BookingStatus::Expired->value => 'danger',
            default => 'gray',
        };
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking_code')->label('Code')->searchable()->copyable()->weight('medium'),
                TextColumn::make('user.name')->label('Rider')->searchable()->toggleable(),
                TextColumn::make('bike.name')->label('Bike')->searchable()->toggleable(),
                TextColumn::make('duration_type')->label('Type')->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => self::statusColor($state)),
                TextColumn::make('total_amount')->label('Total')->money('INR', divideBy: 100)->sortable(),
                TextColumn::make('start_at')->label('Start')->dateTime('d M Y, h:i A')->timezone('Asia/Kolkata')->sortable(),
                TextColumn::make('created_at')->label('Booked')->dateTime('d M Y')->timezone('Asia/Kolkata')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(BookingStatus::cases())->mapWithKeys(fn ($c) => [
                        $c->value => ucfirst($c->value),
                    ])->all()),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label('Booked from'),
                        DatePicker::make('until')->label('Booked until'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                        ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))),
            ])
            ->recordActions([
                ViewAction::make(),
                ...BookingActions::all(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
