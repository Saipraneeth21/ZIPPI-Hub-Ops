<?php

namespace App\Filament\Resources\InstantDispatches;

use App\Filament\Resources\InstantDispatches\Pages\CreateInstantDispatch;
use App\Filament\Resources\InstantDispatches\Pages\EditInstantDispatch;
use App\Filament\Resources\InstantDispatches\Pages\ListInstantDispatches;
use App\Filament\Resources\InstantDispatches\Pages\ViewInstantDispatch;
use App\Filament\Resources\InstantDispatches\Schemas\InstantDispatchForm;
use App\Filament\Resources\InstantDispatches\Schemas\InstantDispatchInfolist;
use App\Filament\Resources\InstantDispatches\Tables\InstantDispatchesTable;
use App\Models\Rental\InstantDispatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class InstantDispatchResource extends Resource
{
    protected static ?string $model = InstantDispatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static ?string $navigationLabel = 'Instant Dispatch';

    protected static string|UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'instant dispatch';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return InstantDispatchForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InstantDispatchInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InstantDispatchesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInstantDispatches::route('/'),
            'create' => CreateInstantDispatch::route('/create'),
            'view' => ViewInstantDispatch::route('/{record}'),
            'edit' => EditInstantDispatch::route('/{record}/edit'),
        ];
    }

    // Counter operation — super_admin / ops per the AdminAccess matrix.
    public static function canViewAny(): bool
    {
        return auth()->user()?->can('instant_dispatch.manage') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('instant_dispatch.manage') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('instant_dispatch.manage') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('instant_dispatch.manage') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('instant_dispatch.manage') ?? false;
    }
}
