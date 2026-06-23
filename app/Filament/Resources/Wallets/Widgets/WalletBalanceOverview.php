<?php

namespace App\Filament\Resources\Wallets\Widgets;

use App\Models\Rental\Wallet;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Header tile for the Wallets list: the combined balance held across all
 * rider wallets (balances are stored in paise).
 */
class WalletBalanceOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalPaise = (int) Wallet::sum('balance');
        $walletCount = Wallet::count();
        $fundedCount = Wallet::where('balance', '>', 0)->count();

        return [
            Stat::make('Total wallet balance', '₹' . number_format($totalPaise / 100, 2))
                ->description($walletCount . ' wallet(s) · ' . $fundedCount . ' funded')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('primary'),
        ];
    }
}
