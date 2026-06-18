<?php

namespace App\Filament\Resources\BikePricings;

use App\Filament\Resources\BikePricings\Pages\CreateBikePricing;
use App\Filament\Resources\BikePricings\Pages\EditBikePricing;
use App\Filament\Resources\BikePricings\Pages\ListBikePricings;
use App\Filament\Resources\BikePricings\Schemas\BikePricingForm;
use App\Filament\Resources\BikePricings\Tables\BikePricingsTable;
use App\Models\Rental\BikePricing;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BikePricingResource extends Resource
{
    protected static ?string $model = BikePricing::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyRupee;


    protected static ?string $navigationLabel = 'Rental Plans';

    protected static ?int $navigationSort = 17;

    protected static ?string $modelLabel = 'plan';

    protected static ?string $pluralModelLabel = 'plans';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return BikePricingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BikePricingsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('bike.category');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBikePricings::route('/'),
            'create' => CreateBikePricing::route('/create'),
            'edit' => EditBikePricing::route('/{record}/edit'),
        ];
    }

    // Pricing management — super_admin / ops per the AdminAccess matrix.
    public static function canViewAny(): bool
    {
        return auth()->user()?->can('pricing.manage') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('pricing.manage') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('pricing.manage') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('pricing.manage') ?? false;
    }
}
