<?php

namespace App\Filament\Resources\Bikes\Tables;

use App\Enums\BikeStatus;
use App\Models\Rental\Bike;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class BikesTable
{
    private static function statusColor(string $state): string
    {
        return match ($state) {
            BikeStatus::Available->value => 'success',
            BikeStatus::Booked->value => 'warning',
            BikeStatus::Maintenance->value => 'info',
            default => 'gray',
        };
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('primaryImage.image_path')
                    ->label('')
                    ->disk(config('filesystems.default'))
                    ->height(40)
                    ->circular(),
                TextColumn::make('name')->searchable()->sortable()->weight('medium'),
                TextColumn::make('registration_no')->label('Reg. no')->searchable()->copyable(),
                TextColumn::make('category.name')->label('Category')->badge()->color('gray'),
                TextColumn::make('hub.name')->label('Hub')->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => self::statusColor($state)),
                TextColumn::make('security_deposit_amount')->label('Deposit')->money('INR', divideBy: 100),
                TextColumn::make('bookings_count')->label('Bookings')->counts('bookings')->badge(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(BikeStatus::cases())->mapWithKeys(fn ($c) => [
                        $c->value => ucfirst($c->value),
                    ])->all()),
                SelectFilter::make('category')->relationship('category', 'name')->searchable()->preload(),
                SelectFilter::make('hub')->relationship('hub', 'name')->searchable()->preload(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                // Quick availability change (§3.4 Availability).
                Action::make('setStatus')
                    ->label('Status')
                    ->icon('heroicon-m-arrow-path')
                    ->schema(fn (Schema $schema): Schema => $schema->components([
                        Select::make('status')
                            ->options(collect(BikeStatus::cases())->mapWithKeys(fn ($c) => [
                                $c->value => ucfirst($c->value),
                            ])->all())
                            ->required(),
                    ]))
                    ->fillForm(fn (Bike $record) => ['status' => $record->status])
                    ->action(function (Bike $record, array $data): void {
                        $record->update(['status' => $data['status']]);
                        Notification::make()->title('Status updated')->success()->send();
                    }),

                // Soft delete — blocked while the bike has confirmed/active bookings.
                DeleteAction::make()
                    ->hidden(fn (Bike $record) => $record->hasBlockingBookings()),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
