<?php

namespace App\Filament\Resources\Staff;

use App\Filament\Resources\Staff\Pages\CreateStaff;
use App\Filament\Resources\Staff\Pages\EditStaff;
use App\Filament\Resources\Staff\Pages\ListStaff;
use App\Filament\Resources\Staff\Schemas\StaffForm;
use App\Filament\Resources\Staff\Tables\StaffTable;
use App\Models\Rental\AdminUser;
use App\Support\Rental\AdminAccess;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Account → Staff: super-admin–only management of role-based staff logins
 * for the Admin Dashboard. Each staff member gets a Name, Email and Role
 * (which drives their permissions via the AdminAccess RBAC matrix).
 */
class StaffResource extends Resource
{
    protected static ?string $model = AdminUser::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Account';

    protected static ?string $navigationLabel = 'Staff';

    protected static ?string $modelLabel = 'staff member';

    protected static ?string $pluralModelLabel = 'staff';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return StaffForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StaffTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaff::route('/'),
            'create' => CreateStaff::route('/create'),
            'edit' => EditStaff::route('/{record}/edit'),
        ];
    }

    // --- RBAC: full-access admins (Super Admin / Admin / Developer / Manager)
    // manage staff logins. ---

    protected static function canManageStaff(): bool
    {
        $user = auth()->user();

        return $user instanceof AdminUser && AdminAccess::hasFullAccess($user);
    }

    public static function canViewAny(): bool
    {
        return static::canManageStaff();
    }

    public static function canCreate(): bool
    {
        return static::canManageStaff();
    }

    public static function canEdit($record): bool
    {
        return static::canManageStaff();
    }

    public static function canDelete($record): bool
    {
        // May remove staff, but never your own account.
        return static::canManageStaff() && auth()->id() !== $record->getKey();
    }
}
