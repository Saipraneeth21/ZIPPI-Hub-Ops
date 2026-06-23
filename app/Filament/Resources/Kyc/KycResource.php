<?php

namespace App\Filament\Resources\Kyc;

use App\Enums\KycStatus;
use App\Filament\Resources\Kyc\Pages\ListKycReviews;
use App\Filament\Resources\Kyc\Pages\ViewKycReview;
use App\Filament\Resources\Kyc\Schemas\KycInfolist;
use App\Filament\Resources\Kyc\Tables\KycTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * KYC review queue (Admin-Dashboard/01-Admin-Modules.md §3.3). A second
 * resource over the User model, scoped to riders who have submitted KYC.
 */
class KycResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'kyc';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;


    protected static ?string $navigationLabel = 'KYC Queue';

    protected static string|UnitEnum|null $navigationGroup = 'Customers';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'KYC review';

    protected static ?string $pluralModelLabel = 'KYC reviews';

    public static function infolist(Schema $schema): Schema
    {
        return KycInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KycTable::configure($table);
    }

    /** Only riders who have actually submitted KYC appear in the queue. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['profile', 'kycDocuments'])
            ->whereHas('profile', fn ($q) => $q->where('kyc_status', '!=', KycStatus::None->value));
    }

    /** Pending-review count badge on the nav item. */
    public static function getNavigationBadge(): ?string
    {
        $count = User::whereHas('profile', fn ($q) => $q->whereIn('kyc_status', [
            KycStatus::Pending->value,
            KycStatus::UnderReview->value,
        ]))->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKycReviews::route('/'),
            'view' => ViewKycReview::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('kyc.review') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('kyc.review') ?? false;
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
