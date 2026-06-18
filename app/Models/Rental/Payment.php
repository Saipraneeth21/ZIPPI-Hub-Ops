<?php
namespace App\Models\Rental;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Payment extends Model
{
    protected $table = 'rental_payments';
    protected $guarded = [];
    protected $casts = ['paid_at' => 'datetime'];
    public function booking(): BelongsTo { return $this->belongsTo(Booking::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
