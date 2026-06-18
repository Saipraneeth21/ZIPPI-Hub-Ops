<?php

namespace App\Filament\Resources\Kyc\Support;

use App\Enums\KycStatus;
use App\Models\User;
use App\Services\Rental\KycService;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;

/**
 * Approve / reject actions for the KYC queue. Both delegate to KycService so
 * the dashboard and the mobile-facing API stay on one code path, and both are
 * gated on the `kyc.review` ability.
 */
final class KycReviewActions
{
    private static function decided(User $record): bool
    {
        return in_array($record->profile?->kyc_status, [
            KycStatus::Approved->value,
            KycStatus::Rejected->value,
        ], true);
    }

    public static function approve(): Action
    {
        return Action::make('approveKyc')
            ->label('Approve')
            ->icon('heroicon-m-check-badge')
            ->color('success')
            ->visible(fn () => auth()->user()?->can('kyc.review') ?? false)
            ->disabled(fn (User $record) => self::decided($record))
            ->requiresConfirmation()
            ->modalHeading('Approve KYC')
            ->modalDescription('This unlocks booking for the rider and marks all documents approved.')
            ->action(function (User $record, KycService $kyc): void {
                $kyc->approve($record, auth()->id());

                Notification::make()->title('KYC approved')->success()->send();
            });
    }

    public static function reject(): Action
    {
        return Action::make('rejectKyc')
            ->label('Reject')
            ->icon('heroicon-m-x-circle')
            ->color('danger')
            ->visible(fn () => auth()->user()?->can('kyc.review') ?? false)
            ->disabled(fn (User $record) => self::decided($record))
            ->schema(fn (Schema $schema): Schema => $schema->components([
                Textarea::make('reason')
                    ->label('Rejection reason')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Shown to the rider — explain what needs fixing.'),
                CheckboxList::make('document_ids')
                    ->label('Flag specific documents (optional)')
                    ->helperText('Leave empty to reject all submitted documents.')
                    ->options(fn (User $record) => $record->kycDocuments
                        ->mapWithKeys(fn ($d) => [
                            $d->id => ucwords(str_replace('_', ' ', $d->document_type))
                                . ($d->document_number_masked ? " ({$d->document_number_masked})" : ''),
                        ])->all()),
            ]))
            ->action(function (User $record, array $data, KycService $kyc): void {
                $kyc->reject(
                    $record,
                    $data['reason'],
                    auth()->id(),
                    $data['document_ids'] ?? [],
                );

                Notification::make()->title('KYC rejected')->danger()->send();
            });
    }
}
