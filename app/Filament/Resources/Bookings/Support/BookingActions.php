<?php

namespace App\Filament\Resources\Bookings\Support;

use App\Models\Rental\Booking;
use App\Models\Rental\Hub;
use App\Services\Rental\BookingService;
use App\Services\Rental\RefundService;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Throwable;

/**
 * Booking lifecycle workflows (Admin-Dashboard/01-Admin-Modules.md §3.5).
 * Each delegates to the domain services so the dashboard and the mobile API
 * share one code path. All are gated on the `orders.manage` ability.
 */
final class BookingActions
{
    private static function canManage(): bool
    {
        return auth()->user()?->can('orders.manage') ?? false;
    }

    /** Cancel an upcoming (pending/confirmed) booking, optionally overriding the refund policy. */
    public static function cancel(): Action
    {
        return Action::make('cancelBooking')
            ->label('Cancel')
            ->icon('heroicon-m-x-circle')
            ->color('danger')
            ->visible(fn (Booking $record) => self::canManage()
                && in_array($record->status, ['pending', 'confirmed'], true))
            ->schema(fn (Schema $schema): Schema => $schema->components([
                Textarea::make('reason')->label('Cancellation reason')->required()->maxLength(255),
                Toggle::make('override_policy')
                    ->label('Override policy (full refund)')
                    ->helperText('Refund the full rental amount regardless of the cancellation window.')
                    ->default(false),
            ]))
            ->action(function (Booking $record, array $data, BookingService $bookings): void {
                try {
                    $result = $bookings->cancel(
                        $record,
                        $data['reason'],
                        auth()->id(),
                        (bool) ($data['override_policy'] ?? false),
                    );
                } catch (Throwable $e) {
                    Notification::make()->title('Cannot cancel')->body($e->getMessage())->danger()->send();

                    return;
                }

                Notification::make()
                    ->title('Booking cancelled')
                    ->body('Rental refund: ₹' . number_format($result['rental_refund'] / 100, 2)
                        . ' · Deposit refund: ₹' . number_format($result['deposit_refund'] / 100, 2))
                    ->success()
                    ->send();
            });
    }

    /** Force-return an active rental (computes late penalty + deposit refund). */
    public static function forceReturn(): Action
    {
        return Action::make('forceReturn')
            ->label('Force return')
            ->icon('heroicon-m-arrow-uturn-left')
            ->color('warning')
            ->visible(fn (Booking $record) => self::canManage() && $record->status === 'active')
            ->requiresConfirmation()
            ->modalDescription('Mark this active rental as returned now. A late penalty may apply and the deposit refund will be initiated.')
            ->schema(fn (Schema $schema): Schema => $schema->components([
                Select::make('return_hub_id')
                    ->label('Return hub')
                    ->options(fn () => Hub::orderBy('name')->pluck('name', 'id'))
                    ->default(fn (Booking $record) => $record->return_hub_id)
                    ->searchable()
                    ->required(),
            ]))
            ->action(function (Booking $record, array $data, BookingService $bookings): void {
                try {
                    $result = $bookings->returnBike($record, (int) $data['return_hub_id'], auth()->id());
                } catch (Throwable $e) {
                    Notification::make()->title('Cannot return')->body($e->getMessage())->danger()->send();

                    return;
                }

                Notification::make()
                    ->title('Rental returned')
                    ->body('Late penalty: ₹' . number_format($result['late_penalty'] / 100, 2)
                        . ' · Deposit refund: ₹' . number_format($result['deposit_refund'] / 100, 2))
                    ->success()
                    ->send();
            });
    }

    /** Issue a manual partial refund with deductions against the booking. */
    public static function applyDeductions(): Action
    {
        return Action::make('applyDeductions')
            ->label('Refund / deduct')
            ->icon('heroicon-m-banknotes')
            ->color('gray')
            ->visible(fn () => self::canManage())
            ->schema(fn (Schema $schema): Schema => $schema->components([
                TextInput::make('amount')
                    ->label('Gross amount (₹)')
                    ->numeric()->minValue(0)->required()->prefix('₹'),
                TextInput::make('deductions')
                    ->label('Deductions (₹)')
                    ->numeric()->minValue(0)->default(0)->prefix('₹'),
                Textarea::make('note')->label('Deduction note')->maxLength(255),
                Select::make('destination')
                    ->options(['wallet' => 'Wallet', 'source' => 'Original source'])
                    ->default('wallet')
                    ->required(),
            ]))
            ->action(function (Booking $record, array $data, RefundService $refunds): void {
                $amount = Money::ofRupees((float) $data['amount']);
                $deductions = Money::ofRupees((float) ($data['deductions'] ?? 0));

                if ($deductions->greaterThan($amount)) {
                    Notification::make()->title('Deductions exceed amount')->danger()->send();

                    return;
                }

                try {
                    $refund = $refunds->process(
                        $record,
                        'partial',
                        $amount,
                        $deductions,
                        $data['note'] ?? null,
                        $data['destination'],
                        'manual_' . $record->id . '_' . now()->timestamp,
                    );
                } catch (Throwable $e) {
                    Notification::make()->title('Refund failed')->body($e->getMessage())->danger()->send();

                    return;
                }

                Notification::make()
                    ->title('Refund recorded')
                    ->body('Net ₹' . number_format($refund->amount / 100, 2) . ' to ' . $data['destination'])
                    ->success()
                    ->send();
            });
    }

    /** @return array<int, Action> */
    public static function all(): array
    {
        return [self::cancel(), self::forceReturn(), self::applyDeductions()];
    }
}
