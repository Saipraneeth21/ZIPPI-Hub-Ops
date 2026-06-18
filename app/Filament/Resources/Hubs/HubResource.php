<?php

namespace App\Filament\Resources\Hubs;

use App\Filament\Resources\Hubs\Pages\CreateHub;
use App\Filament\Resources\Hubs\Pages\EditHub;
use App\Filament\Resources\Hubs\Pages\ListHubs;
use App\Filament\Resources\Hubs\Schemas\HubForm;
use App\Filament\Resources\Hubs\Tables\HubsTable;
use App\Models\Rental\Hub;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HubResource extends Resource
{
    protected static ?string $model = Hub::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;


    protected static ?string $navigationLabel = 'Hubs';

    protected static ?int $navigationSort = 9;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return HubForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HubsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHubs::route('/'),
            'create' => CreateHub::route('/create'),
            'edit' => EditHub::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }
}
