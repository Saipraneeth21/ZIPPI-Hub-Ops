<?php
namespace App\Models\Rental;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class BookingStatusHistory extends Model
{
    protected $table = 'rental_booking_status_history';
    protected $guarded = [];
    public function booking(): BelongsTo { return $this->belongsTo(Booking::class); }
}
