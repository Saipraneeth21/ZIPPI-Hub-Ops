<?php

namespace App\Filament\Resources\Kyc\Tables;

use App\Enums\KycStatus;
use App\Filament\Resources\Kyc\Support\KycReviewActions;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KycTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('medium'),
                TextColumn::make('mobile')
                    ->searchable()
                    ->formatStateUsing(fn ($state, $record) => $record->country_code . ' ' . $state),
                TextColumn::make('profile.kyc_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => ucwords(str_replace('_', ' ', $state ?? 'none')))
                    ->color(fn (?string $state) => match ($state) {
                        KycStatus::Approved->value => 'success',
                        KycStatus::Rejected->value => 'danger',
                        KycStatus::Pending->value, KycStatus::UnderReview->value => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('kyc_documents_count')
                    ->label('Docs')
                    ->counts('kycDocuments')
                    ->badge(),
                TextColumn::make('profile.updated_at')
                    ->label('Submitted')
                    ->since()
                    ->timezone('Asia/Kolkata')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('kyc_status')
                    ->label('Status')
                    ->options(collect([
                        KycStatus::Pending, KycStatus::UnderReview,
                        KycStatus::Approved, KycStatus::Rejected,
                    ])->mapWithKeys(fn ($c) => [
                        $c->value => ucwords(str_replace('_', ' ', $c->value)),
                    ])->all())
                    ->default(KycStatus::Pending->value)
                    ->query(fn ($query, array $data) => ($data['value'] ?? null)
                        ? $query->whereHas('profile', fn ($q) => $q->where('kyc_status', $data['value']))
                        : $query),
            ])
            ->recordActions([
                ViewAction::make(),
                KycReviewActions::approve(),
                KycReviewActions::reject(),
            ])
            ->defaultSort('updated_at', 'asc'); // oldest submissions first
    }
}
