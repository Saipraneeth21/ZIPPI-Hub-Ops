<?php
namespace App\Models\Rental;
use App\Enums\KycStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class KycDocument extends Model
{
    protected $table = 'rental_kyc_documents';
    protected $guarded = [];
    protected $casts = [
        'status' => KycStatus::class,
        'verified_at' => 'datetime',
    ];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
