<?php

namespace App\Filament\Resources\MaintenanceRecords;

use App\Filament\Resources\MaintenanceRecords\Pages\CreateMaintenanceRecord;
use App\Filament\Resources\MaintenanceRecords\Pages\EditMaintenanceRecord;
use App\Filament\Resources\MaintenanceRecords\Pages\ListMaintenanceRecords;
use App\Filament\Resources\MaintenanceRecords\Schemas\MaintenanceRecordForm;
use App\Filament\Resources\MaintenanceRecords\Tables\MaintenanceRecordsTable;
use App\Models\Rental\MaintenanceRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MaintenanceRecordResource extends Resource
{
    protected static ?string $model = MaintenanceRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $navigationLabel = 'Maintenance Records';

    protected static string|UnitEnum|null $navigationGroup = 'Fleet';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return MaintenanceRecordForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaintenanceRecordsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaintenanceRecords::route('/'),
            'create' => CreateMaintenanceRecord::route('/create'),
            'edit' => EditMaintenanceRecord::route('/{record}/edit'),
        ];
    }

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
