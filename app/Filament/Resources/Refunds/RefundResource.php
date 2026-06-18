<?php

namespace App\Filament\Resources\Refunds;

use App\Filament\Resources\Refunds\Pages\ListRefunds;
use App\Filament\Resources\Refunds\Pages\ViewRefund;
use App\Filament\Resources\Refunds\Schemas\RefundInfolist;
use App\Filament\Resources\Refunds\Tables\RefundsTable;
use App\Models\Rental\Refund;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RefundResource extends Resource
{
    protected static ?string $model = Refund::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnLeft;


    protected static ?string $navigationLabel = 'Refunds';

    protected static ?int $navigationSort = 16;

    protected static ?string $recordTitleAttribute = 'refund_reference';

    public static function infolist(Schema $schema): Schema
    {
        return RefundInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RefundsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'booking']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRefunds::route('/'),
            'view' => ViewRefund::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('payments.view') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('payments.view') ?? false;
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
