<?php

namespace App\Filament\Resources\CustomerDocuments;

use App\Filament\Resources\CustomerDocuments\Pages\CreateCustomerDocument;
use App\Filament\Resources\CustomerDocuments\Pages\EditCustomerDocument;
use App\Filament\Resources\CustomerDocuments\Pages\ListCustomerDocuments;
use App\Filament\Resources\CustomerDocuments\Pages\ViewCustomerDocument;
use App\Filament\Resources\CustomerDocuments\Schemas\CustomerDocumentForm;
use App\Filament\Resources\CustomerDocuments\Schemas\CustomerDocumentInfolist;
use App\Filament\Resources\CustomerDocuments\Tables\CustomerDocumentsTable;
use App\Models\Rental\KycDocument;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Customers Documents (Admin-Dashboard §3.3) — every KYC document a customer
 * has submitted, with its verification status and date. Top-level feature
 * gated on `kyc.review`; files are streamed behind short-TTL signed URLs.
 */
class CustomerDocumentResource extends Resource
{
    protected static ?string $model = KycDocument::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Customer Documents';

    protected static ?int $navigationSort = 7;

    protected static ?string $modelLabel = 'customer document';

    protected static ?string $pluralModelLabel = 'customers documents';

    protected static ?string $recordTitleAttribute = 'document_number_masked';

    public static function form(Schema $schema): Schema
    {
        return CustomerDocumentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CustomerDocumentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerDocumentsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerDocuments::route('/'),
            'create' => CreateCustomerDocument::route('/create'),
            'view' => ViewCustomerDocument::route('/{record}'),
            'edit' => EditCustomerDocument::route('/{record}/edit'),
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
        return auth()->user()?->can('kyc.review') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('kyc.review') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('kyc.review') ?? false;
    }
}
