<?php

namespace App\Filament\Resources\Kyc\Schemas;

use App\Enums\KycStatus;
use App\Http\Controllers\Admin\KycDocumentController;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KycInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Rider')
                ->columns(3)
                ->schema([
                    TextEntry::make('name'),
                    TextEntry::make('mobile')
                        ->formatStateUsing(fn ($state, $record) => $record->country_code . ' ' . $state),
                    TextEntry::make('email')->placeholder('—'),
                    TextEntry::make('profile.kyc_status')
                        ->label('KYC status')
                        ->badge()
                        ->formatStateUsing(fn (?string $state) => ucwords(str_replace('_', ' ', $state ?? 'none')))
                        ->color(fn (?string $state) => match ($state) {
                            KycStatus::Approved->value => 'success',
                            KycStatus::Rejected->value => 'danger',
                            KycStatus::Pending->value, KycStatus::UnderReview->value => 'warning',
                            default => 'gray',
                        }),
                    TextEntry::make('profile.updated_at')
                        ->label('Submitted')
                        ->dateTime('d M Y, h:i A')
                        ->timezone('Asia/Kolkata'),
                ]),

            Section::make('Documents')
                ->description('Document links are signed and expire after '
                    . KycDocumentController::LINK_TTL_MINUTES . ' minutes.')
                ->schema([
                    RepeatableEntry::make('kycDocuments')
                        ->hiddenLabel()
                        ->columns(4)
                        ->schema([
                            TextEntry::make('document_type')
                                ->label('Type')
                                ->formatStateUsing(fn (string $state) => ucwords(str_replace('_', ' ', $state))),
                            TextEntry::make('document_number_masked')
                                ->label('Number')
                                ->placeholder('—'),
                            TextEntry::make('status')
                                ->badge()
                                ->formatStateUsing(fn ($state) => ucfirst($state instanceof \App\Enums\KycStatus ? $state->value : $state))
                                ->color(fn ($state) => match ($state instanceof \App\Enums\KycStatus ? $state->value : $state) {
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    default => 'warning',
                                }),
                            TextEntry::make('file_path')
                                ->label('File')
                                ->formatStateUsing(fn () => 'View document ↗')
                                ->color('primary')
                                ->url(fn ($record) => KycDocumentController::signedUrl($record), shouldOpenInNewTab: true),
                        ]),
                ]),
        ]);
    }
}
