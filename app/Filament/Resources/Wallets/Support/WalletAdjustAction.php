<?php

namespace App\Filament\Resources\Wallets\Support;

use App\Models\Rental\AuditLog;
use App\Models\Rental\Wallet;
use App\Services\Rental\WalletService;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Request;
use Throwable;

/**
 * Super-admin-only wallet adjustment (Admin-Dashboard §3.6). Credits or debits
 * a rider's wallet with a mandatory reason, routed through WalletService and
 * recorded in the audit log.
 */
final class WalletAdjustAction
{
    public static function make(): Action
    {
        return Action::make('adjustWallet')
            ->label('Adjust balance')
            ->icon('heroicon-m-adjustments-horizontal')
            ->color('warning')
            ->visible(fn () => auth()->user()?->can('wallet.adjust') ?? false)
            ->modalHeading('Adjust wallet balance')
            ->schema(fn (Schema $schema): Schema => $schema->components([
                Select::make('direction')
                    ->options(['credit' => 'Credit (add funds)', 'debit' => 'Debit (remove funds)'])
                    ->default('credit')
                    ->required(),
                TextInput::make('amount')
                    ->label('Amount (₹)')
                    ->numeric()->required()->minValue(1)->prefix('₹'),
                Textarea::make('reason')->label('Reason')->required()->maxLength(255),
            ]))
            ->action(function (Wallet $record, array $data, WalletService $wallets): void {
                $amount = Money::ofRupees((float) $data['amount']);
                $direction = $data['direction'];
                $before = (int) $record->balance;

                if ($direction === 'debit' && $amount->paise > $before) {
                    Notification::make()->title('Insufficient balance')->danger()->send();

                    return;
                }

                try {
                    $txn = $direction === 'credit'
                        ? $wallets->credit($record->user_id, $amount, 'admin_adjustment', null, $data['reason'])
                        : $wallets->debit($record->user_id, $amount, 'admin_adjustment', null, $data['reason']);
                } catch (Throwable $e) {
                    Notification::make()->title('Adjustment failed')->body($e->getMessage())->danger()->send();

                    return;
                }

                AuditLog::create([
                    'actor_id' => auth()->id(),
                    'actor_type' => 'admin',
                    'action' => 'wallet.' . $direction,
                    'entity_type' => 'wallet',
                    'entity_id' => $record->id,
                    'before' => ['balance' => $before],
                    'after' => ['balance' => $txn->balance_after, 'amount' => $amount->paise, 'reason' => $data['reason']],
                    'ip_address' => Request::ip(),
                ]);

                Notification::make()
                    ->title('Wallet ' . $direction . 'ed')
                    ->body('New balance: ₹' . number_format($txn->balance_after / 100, 2))
                    ->success()
                    ->send();
            });
    }
}
