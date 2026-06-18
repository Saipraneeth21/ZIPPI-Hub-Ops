<?php

namespace App\Filament\Resources\Bookings;

use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Filament\Resources\Bookings\Pages\ViewBooking;
use App\Filament\Resources\Bookings\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\Bookings\RelationManagers\RefundsRelationManager;
use App\Filament\Resources\Bookings\RelationManagers\StatusHistoryRelationManager;
use App\Filament\Resources\Bookings\Schemas\BookingInfolist;
use App\Filament\Resources\Bookings\Tables\BookingsTable;
use App\Models\Rental\Booking;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Bookings';

    protected static ?string $modelLabel = 'order';

    protected static ?string $pluralModelLabel = 'orders';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'booking_code';

    public static function infolist(Schema $schema): Schema
    {
        return BookingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BookingsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'bike']);
    }

    public static function getRelations(): array
    {
        return [
            PaymentsRelationManager::class,
            RefundsRelationManager::class,
            StatusHistoryRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookings::route('/'),
            'view' => ViewBooking::route('/{record}'),
        ];
    }

    // Orders are operated, never created/edited/deleted directly.
    public static function canViewAny(): bool
    {
        return auth()->user()?->can('orders.view') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('orders.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
