<?php

namespace App\Filament\Resources\CustomerDocuments\Tables;

use App\Filament\Support\Filters\DateRangeFilter;
use App\Enums\KycStatus;
use App\Filament\Resources\CustomerDocuments\Schemas\CustomerDocumentForm;
use App\Filament\Resources\CustomerDocuments\Support\RedFlagUserAction;
use App\Http\Controllers\Admin\KycDocumentController;
use App\Models\Rental\KycDocument;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CustomerDocumentsTable
{
    public static function statusColor(KycStatus|string $state): string
    {
        $value = $state instanceof KycStatus ? $state->value : $state;

        return match ($value) {
            KycStatus::Approved->value => 'success',
            KycStatus::Rejected->value => 'danger',
            KycStatus::UnderReview->value => 'info',
            KycStatus::Pending->value => 'warning',
            default => 'gray',
        };
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('User')->searchable()->sortable()->weight('medium')
                    ->description(fn (KycDocument $record) => $record->user?->email),
                IconColumn::make('user.profile.is_red_flagged')
                    ->label('Flagged')
                    ->boolean()
                    ->trueIcon('heroicon-s-flag')
                    ->falseIcon('heroicon-o-flag')
                    ->trueColor('danger')
                    ->falseColor('gray')
                    ->tooltip(fn (KycDocument $record) => $record->user?->profile?->red_flag_reason),
                TextColumn::make('document_type')
                    ->label('Document type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => CustomerDocumentForm::TYPES[$state] ?? str($state)->headline()),
                TextColumn::make('document_number_masked')->label('Document number')->placeholder('—')->searchable(),
                TextColumn::make('status')
                    ->label('Verification status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => str($state instanceof KycStatus ? $state->value : $state)->headline())
                    ->color(fn ($state) => self::statusColor($state)),
                TextColumn::make('verified_at')
                    ->label('Date of verification')
                    ->dateTime('d M Y, h:i A')
                    ->timezone('Asia/Kolkata')
                    ->placeholder('Not verified')
                    ->sortable(),
                TextColumn::make('created_at')->label('Submitted')->dateTime('d M Y')->timezone('Asia/Kolkata')->sortable()->toggleable(),
            ])
            ->filters([
                DateRangeFilter::make(),
                SelectFilter::make('status')
                    ->label('Verification status')
                    ->options(collect(KycStatus::cases())->mapWithKeys(fn ($c) => [
                        $c->value => str($c->value)->headline(),
                    ])->all()),
                SelectFilter::make('document_type')
                    ->label('Document type')
                    ->options(CustomerDocumentForm::TYPES),
                TernaryFilter::make('is_red_flagged')
                    ->label('Red flagged')
                    ->queries(
                        true: fn ($query) => $query->whereHas('user.profile', fn ($q) => $q->where('is_red_flagged', true)),
                        false: fn ($query) => $query->whereDoesntHave('user.profile', fn ($q) => $q->where('is_red_flagged', true)),
                        blank: fn ($query) => $query,
                    ),
            ])
            ->recordActions([
                Action::make('file')
                    ->label('File')
                    ->icon('heroicon-m-paper-clip')
                    ->color('gray')
                    ->url(fn (KycDocument $record) => $record->file_path ? KycDocumentController::signedUrl($record) : null)
                    ->openUrlInNewTab()
                    ->visible(fn (KycDocument $record) => filled($record->file_path)),
                ViewAction::make(),
                EditAction::make(),
                RedFlagUserAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
