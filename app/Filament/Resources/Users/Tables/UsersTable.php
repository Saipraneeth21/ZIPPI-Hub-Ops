<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Support\Filters\DateRangeFilter;
use App\Enums\KycStatus;
use App\Filament\Resources\Users\Support\BlockToggleAction;
use App\Filament\Resources\Users\Support\RedFlagToggleAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('mobile')
                    ->label('Mobile')
                    ->searchable()
                    ->formatStateUsing(fn ($state, $record) => $record->country_code . ' ' . $state),

                TextColumn::make('email')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('—'),

                TextColumn::make('profile.kyc_status')
                    ->label('KYC')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => ucwords(str_replace('_', ' ', $state ?? 'none')))
                    ->color(fn (?string $state) => match ($state) {
                        KycStatus::Approved->value => 'success',
                        KycStatus::Rejected->value => 'danger',
                        KycStatus::Pending->value, KycStatus::UnderReview->value => 'warning',
                        default => 'gray',
                    }),

                IconColumn::make('profile.is_blocked')
                    ->label('Blocked')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),

                IconColumn::make('profile.is_red_flagged')
                    ->label('Flagged')
                    ->boolean()
                    ->trueIcon('heroicon-s-flag')
                    ->falseIcon('heroicon-o-flag')
                    ->trueColor('danger')
                    ->falseColor('gray')
                    ->tooltip(fn ($record) => $record->profile?->red_flag_reason),

                TextColumn::make('wallet.balance')
                    ->label('Wallet')
                    ->money('INR', divideBy: 100)
                    ->placeholder('₹0.00')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->timezone('Asia/Kolkata'),
            ])
            ->filters([
                DateRangeFilter::make(),
                SelectFilter::make('kyc_status')
                    ->label('KYC status')
                    ->options(collect(KycStatus::cases())->mapWithKeys(fn ($c) => [
                        $c->value => ucwords(str_replace('_', ' ', $c->value)),
                    ])->all())
                    ->query(fn ($query, array $data) => ($data['value'] ?? null)
                        ? $query->whereHas('profile', fn ($q) => $q->where('kyc_status', $data['value']))
                        : $query),

                TernaryFilter::make('blocked')
                    ->label('Blocked')
                    ->queries(
                        true: fn ($query) => $query->whereHas('profile', fn ($q) => $q->where('is_blocked', true)),
                        false: fn ($query) => $query->whereDoesntHave('profile', fn ($q) => $q->where('is_blocked', true)),
                        blank: fn ($query) => $query,
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
                BlockToggleAction::make(),
                RedFlagToggleAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
