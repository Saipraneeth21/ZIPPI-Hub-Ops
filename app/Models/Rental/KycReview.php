<?php
namespace App\Models\Rental;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class KycReview extends Model
{
    protected $table = 'rental_kyc_reviews';
    protected $guarded = [];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
