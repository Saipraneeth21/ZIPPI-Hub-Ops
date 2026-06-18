<?php
namespace App\Models\Rental;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Refund extends Model
{
    protected $table = 'rental_refunds';
    protected $guarded = [];
    protected $casts = ['processed_at' => 'datetime'];
    public function booking(): BelongsTo { return $this->belongsTo(Booking::class); }
    public function payment(): BelongsTo { return $this->belongsTo(Payment::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
