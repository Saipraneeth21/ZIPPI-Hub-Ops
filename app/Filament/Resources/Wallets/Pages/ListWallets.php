<?php

namespace App\Filament\Resources\Wallets\Pages;

use App\Filament\Resources\Wallets\Widgets\WalletBalanceOverview;
use App\Filament\Resources\Wallets\WalletResource;
use Filament\Resources\Pages\ListRecords;

class ListWallets extends ListRecords
{
    protected static string $resource = WalletResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            WalletBalanceOverview::class,
        ];
    }
}
