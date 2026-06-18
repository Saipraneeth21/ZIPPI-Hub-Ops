<?php

namespace App\Services\Rental;

use App\Models\Rental\Wallet;
use App\Models\Rental\WalletTransaction;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Wallet ledger. Every balance change writes an immutable transaction row with
 * the running balance_after. Balance never goes negative.
 * See Database/03-Data-Relationships.md §2.3.
 */
class WalletService
{
    public function forUser(int $userId): Wallet
    {
        return Wallet::firstOrCreate(['user_id' => $userId], ['balance' => 0, 'currency' => 'INR']);
    }

    public function credit(int $userId, Money $amount, string $sourceType, ?int $sourceId, string $description): WalletTransaction
    {
        return DB::transaction(function () use ($userId, $amount, $sourceType, $sourceId, $description) {
            $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->firstOrCreate(
                ['user_id' => $userId], ['balance' => 0, 'currency' => 'INR']
            );
            $wallet->balance += $amount->paise;
            $wallet->save();

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $userId,
                'direction' => 'credit',
                'amount' => $amount->paise,
                'balance_after' => $wallet->balance,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'description' => $description,
            ]);
        });
    }

    public function debit(int $userId, Money $amount, string $sourceType, ?int $sourceId, string $description): WalletTransaction
    {
        return DB::transaction(function () use ($userId, $amount, $sourceType, $sourceId, $description) {
            $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->firstOrCreate(
                ['user_id' => $userId], ['balance' => 0, 'currency' => 'INR']
            );
            if ($wallet->balance < $amount->paise) {
                throw new RuntimeException('Insufficient wallet balance.');
            }
            $wallet->balance -= $amount->paise;
            $wallet->save();

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $userId,
                'direction' => 'debit',
                'amount' => $amount->paise,
                'balance_after' => $wallet->balance,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'description' => $description,
            ]);
        });
    }
}
