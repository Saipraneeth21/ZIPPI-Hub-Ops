<?php

namespace App\Filament\Resources\IncidentReports;

use App\Filament\Resources\IncidentReports\Pages\CreateIncidentReport;
use App\Filament\Resources\IncidentReports\Pages\EditIncidentReport;
use App\Filament\Resources\IncidentReports\Pages\ListIncidentReports;
use App\Filament\Resources\IncidentReports\Schemas\IncidentReportForm;
use App\Filament\Resources\IncidentReports\Tables\IncidentReportsTable;
use App\Models\Rental\IncidentReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class IncidentReportResource extends Resource
{
    protected static ?string $model = IncidentReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?string $navigationLabel = 'Incident Reports';

    protected static string|UnitEnum|null $navigationGroup = 'Fleet';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return IncidentReportForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IncidentReportsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIncidentReports::route('/'),
            'create' => CreateIncidentReport::route('/create'),
            'edit' => EditIncidentReport::route('/{record}/edit'),
        ];
    }

    // RBAC: bike incidents are managed by the same roles that manage the fleet.
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
