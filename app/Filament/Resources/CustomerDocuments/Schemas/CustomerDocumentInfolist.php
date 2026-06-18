<?php

namespace App\Filament\Resources\CustomerDocuments\Schemas;

use App\Enums\KycStatus;
use App\Filament\Resources\CustomerDocuments\Tables\CustomerDocumentsTable;
use App\Http\Controllers\Admin\KycDocumentController;
use App\Models\Rental\KycDocument;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerDocumentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Document')
                ->columns(2)
                ->schema([
                    TextEntry::make('user.name')->label('User')
                        ->hint(fn (KycDocument $record) => $record->user?->email),
                    TextEntry::make('document_type')->label('Document type')->badge()
                        ->formatStateUsing(fn ($state) => CustomerDocumentForm::TYPES[$state] ?? str($state)->headline()),
                    TextEntry::make('document_number_masked')->label('Document number')->placeholder('—'),
                    TextEntry::make('file_path')->label('Document file')
                        ->placeholder('No file')
                        ->formatStateUsing(fn () => 'Open document')
                        ->url(fn (KycDocument $record) => $record->file_path ? KycDocumentController::signedUrl($record) : null, true)
                        ->color('primary'),
                ]),

            Section::make('Verification')
                ->columns(2)
                ->schema([
                    TextEntry::make('status')->label('Verification status')->badge()
                        ->formatStateUsing(fn ($state) => str($state instanceof KycStatus ? $state->value : $state)->headline())
                        ->color(fn ($state) => CustomerDocumentsTable::statusColor($state)),
                    TextEntry::make('verified_at')->label('Date of verification')
                        ->dateTime('d M Y, h:i A')->timezone('Asia/Kolkata')->placeholder('Not verified'),
                ]),
        ]);
    }
}
