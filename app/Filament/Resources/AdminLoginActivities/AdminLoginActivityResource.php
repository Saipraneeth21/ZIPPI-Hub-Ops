<?php

namespace App\Filament\Resources\AdminLoginActivities;

use App\Filament\Resources\AdminLoginActivities\Pages\ListAdminLoginActivities;
use App\Filament\Resources\AdminLoginActivities\Tables\AdminLoginActivitiesTable;
use App\Models\Rental\AdminLoginActivity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AdminLoginActivityResource extends Resource
{
    protected static ?string $model = AdminLoginActivity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFingerPrint;


    protected static ?string $navigationLabel = 'Login Activities';

    protected static ?int $navigationSort = 13;

    protected static ?string $modelLabel = 'login activity';

    protected static ?string $pluralModelLabel = 'login activities';

    public static function table(Table $table): Table
    {
        return AdminLoginActivitiesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('admin');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdminLoginActivities::route('/'),
        ];
    }

    // Security trail — super_admin only (audit ability).
    public static function canViewAny(): bool
    {
        return auth()->user()?->can('audit.view') ?? false;
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
