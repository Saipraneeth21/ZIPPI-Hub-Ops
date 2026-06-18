<?php

namespace App\Filament\Resources\Payments\Support;

use App\Models\Rental\Payment;
use App\Services\Rental\RefundService;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Throwable;

/**
 * Initiate a refund against a captured payment (Admin-Dashboard §3.6).
 * Idempotent, reason mandatory, and refunds at/above the configured threshold
 * require a super_admin. Gated on `refunds.manage`.
 */
final class InitiateRefundAction
{
    public static function thresholdPaise(): int
    {
        return (int) config('rental.refund_super_admin_threshold_paise', 500000);
    }

    public static function make(): Action
    {
        return Action::make('initiateRefund')
            ->label('Refund')
            ->icon('heroicon-m-arrow-uturn-left')
            ->color('warning')
            ->visible(fn (Payment $record) => (auth()->user()?->can('refunds.manage') ?? false)
                && $record->status === 'captured')
            ->schema(fn (Schema $schema): Schema => $schema->components([
                TextInput::make('amount')
                    ->label('Refund amount (₹)')
                    ->numeric()->required()->minValue(1)->prefix('₹')
                    ->default(fn (Payment $record) => $record->amount / 100)
                    ->helperText(fn () => 'Refunds of ₹' . number_format(self::thresholdPaise() / 100)
                        . ' or more require a super admin.'),
                Textarea::make('reason')->label('Reason')->required()->maxLength(255),
                Select::make('destination')
                    ->options(['wallet' => 'Wallet', 'source' => 'Original source'])
                    ->default('wallet')
                    ->required(),
            ]))
            ->action(function (Payment $record, array $data, RefundService $refunds): void {
                $amount = Money::ofRupees((float) $data['amount']);

                if ($amount->paise > (int) $record->amount) {
                    Notification::make()->title('Amount exceeds the payment')->danger()->send();

                    return;
                }

                // Threshold gate: large refunds are super_admin-only.
                if ($amount->paise >= self::thresholdPaise() && ! (auth()->user()?->isSuperAdmin() ?? false)) {
                    Notification::make()
                        ->title('Super admin approval required')
                        ->body('Refunds of ₹' . number_format(self::thresholdPaise() / 100) . ' or more must be issued by a super admin.')
                        ->danger()
                        ->send();

                    return;
                }

                $booking = $record->booking;
                if (! $booking) {
                    Notification::make()->title('Payment has no booking')->danger()->send();

                    return;
                }

                try {
                    $refund = $refunds->process(
                        $booking,
                        'partial',
                        $amount,
                        Money::zero(),
                        $data['reason'],
                        $data['destination'],
                        'admin_refund_' . $record->id . '_' . now()->timestamp,
                    );
                } catch (Throwable $e) {
                    Notification::make()->title('Refund failed')->body($e->getMessage())->danger()->send();

                    return;
                }

                Notification::make()
                    ->title('Refund initiated')
                    ->body('₹' . number_format($refund->amount / 100, 2) . ' to ' . $data['destination'])
                    ->success()
                    ->send();
            });
    }
}
