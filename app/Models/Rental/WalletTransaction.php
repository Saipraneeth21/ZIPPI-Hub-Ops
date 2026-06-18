<?php
namespace App\Models\Rental;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class WalletTransaction extends Model
{
    protected $table = 'rental_wallet_transactions';
    protected $guarded = [];
    public function wallet(): BelongsTo { return $this->belongsTo(Wallet::class); }
}
