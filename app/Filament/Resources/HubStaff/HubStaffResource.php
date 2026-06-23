<?php

namespace App\Filament\Resources\HubStaff;

use App\Enums\AdminRole;
use App\Filament\Resources\HubStaff\Pages\CreateHubStaff;
use App\Filament\Resources\HubStaff\Pages\EditHubStaff;
use App\Filament\Resources\HubStaff\Pages\ListHubStaff;
use App\Filament\Resources\HubStaff\Schemas\HubStaffForm;
use App\Filament\Resources\HubStaff\Tables\HubStaffTable;
use App\Models\Rental\AdminUser;
use App\Models\Rental\HubStaff;
use App\Support\Rental\AdminAccess;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Hub Staff Login: on-site staff accounts tied to a hub. Created by admins and
 * supervisors. Each record holds the staff member's login credentials plus an
 * employee code and photo.
 */
class HubStaffResource extends Resource
{
    protected static ?string $model = HubStaff::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'Account';

    protected static ?string $navigationLabel = 'Hub Staff Login';

    protected static ?string $modelLabel = 'hub staff member';

    protected static ?string $pluralModelLabel = 'hub staff';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return HubStaffForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HubStaffTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHubStaff::route('/'),
            'create' => CreateHubStaff::route('/create'),
            'edit' => EditHubStaff::route('/{record}/edit'),
        ];
    }

    /**
     * Admins and supervisors manage hub staff. Full-access roles (Super Admin /
     * Admin / Developer / Manager) qualify via AdminAccess; Supervisor is
     * granted explicitly.
     */
    protected static function canManageHubStaff(): bool
    {
        $user = auth()->user();

        if (! $user instanceof AdminUser) {
            return false;
        }

        return AdminAccess::hasFullAccess($user) || $user->role === AdminRole::Supervisor;
    }

    public static function canViewAny(): bool
    {
        return static::canManageHubStaff();
    }

    public static function canCreate(): bool
    {
        return static::canManageHubStaff();
    }

    public static function canEdit($record): bool
    {
        return static::canManageHubStaff();
    }

    public static function canDelete($record): bool
    {
        return static::canManageHubStaff();
    }
}
