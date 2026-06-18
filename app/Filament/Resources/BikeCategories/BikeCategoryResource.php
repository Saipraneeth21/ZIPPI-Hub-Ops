<?php

namespace App\Filament\Resources\BikeCategories;

use App\Filament\Resources\BikeCategories\Pages\CreateBikeCategory;
use App\Filament\Resources\BikeCategories\Pages\EditBikeCategory;
use App\Filament\Resources\BikeCategories\Pages\ListBikeCategories;
use App\Filament\Resources\BikeCategories\Schemas\BikeCategoryForm;
use App\Filament\Resources\BikeCategories\Tables\BikeCategoriesTable;
use App\Models\Rental\BikeCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BikeCategoryResource extends Resource
{
    protected static ?string $model = BikeCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;


    protected static ?string $navigationLabel = 'Bike Models';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return BikeCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BikeCategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBikeCategories::route('/'),
            'create' => CreateBikeCategory::route('/create'),
            'edit' => EditBikeCategory::route('/{record}/edit'),
        ];
    }

    // Fleet management — super_admin / ops per the AdminAccess matrix.
    public static function canViewAny(): bool
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

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('bikes.manage') ?? false;
    }
}
