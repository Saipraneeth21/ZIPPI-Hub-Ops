<?php

namespace App\Filament\Resources\RedFlaggedUsers;

use App\Enums\AdminRole;
use App\Filament\Resources\RedFlaggedUsers\Pages\ListRedFlaggedUsers;
use App\Filament\Resources\RedFlaggedUsers\Tables\RedFlaggedUsersTable;
use App\Models\Rental\AdminUser;
use App\Models\User;
use App\Support\Rental\AdminAccess;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Account → Red Flag: the list of riders currently red-flagged for repeated
 * issues. Shows who they are (name + mobile) and lets full-access admins /
 * supervisors clear the flag.
 */
class RedFlaggedUserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static string|UnitEnum|null $navigationGroup = 'Account';

    protected static ?string $navigationLabel = 'Red Flag';

    protected static ?string $modelLabel = 'red-flagged user';

    protected static ?string $pluralModelLabel = 'red-flagged users';

    protected static ?int $navigationSort = 3;

    /** Only show riders whose profile is currently red-flagged. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('profile', fn (Builder $q) => $q->where('is_red_flagged', true))
            ->with('profile');
    }

    /** Badge with the live count of flagged riders. */
    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()->count() ?: null;
    }

    public static function table(Table $table): Table
    {
        return RedFlaggedUsersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRedFlaggedUsers::route('/'),
        ];
    }

    // Full-access super admins and supervisors only.
    protected static function canManage(): bool
    {
        $user = auth()->user();

        if (! $user instanceof AdminUser) {
            return false;
        }

        return AdminAccess::hasFullAccess($user) || $user->role === AdminRole::Supervisor;
    }

    public static function canViewAny(): bool
    {
        return static::canManage();
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
