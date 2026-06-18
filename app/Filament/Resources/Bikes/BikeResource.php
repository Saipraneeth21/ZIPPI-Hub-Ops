<?php

namespace App\Filament\Resources\Bikes;

use App\Filament\Resources\Bikes\Pages\CreateBike;
use App\Filament\Resources\Bikes\Pages\EditBike;
use App\Filament\Resources\Bikes\Pages\ListBikes;
use App\Filament\Resources\Bikes\Pages\ViewBike;
use App\Filament\Resources\Bikes\RelationManagers\ImagesRelationManager;
use App\Filament\Resources\Bikes\RelationManagers\PricingRelationManager;
use App\Filament\Resources\Bikes\Schemas\BikeForm;
use App\Filament\Resources\Bikes\Schemas\BikeInfolist;
use App\Filament\Resources\Bikes\Tables\BikesTable;
use App\Models\Rental\Bike;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BikeResource extends Resource
{
    protected static ?string $model = Bike::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;


    protected static ?string $navigationLabel = 'Bikes';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return BikeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BikeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BikesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['category', 'hub', 'primaryImage'])
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getRelations(): array
    {
        return [
            PricingRelationManager::class,
            ImagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBikes::route('/'),
            'create' => CreateBike::route('/create'),
            'view' => ViewBike::route('/{record}'),
            'edit' => EditBike::route('/{record}/edit'),
        ];
    }

    // Fleet management — super_admin / ops per the AdminAccess matrix.
    public static function canViewAny(): bool
    {
        return auth()->user()?->can('bikes.manage') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('bikes.manage') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('bikes.manage') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('bikes.manage') ?? false;
    }

    /** Soft delete only, and never while confirmed/active bookings exist. */
    public static function canDelete($record): bool
    {
        return (auth()->user()?->can('bikes.manage') ?? false)
            && ! $record->hasBlockingBookings();
    }
}
