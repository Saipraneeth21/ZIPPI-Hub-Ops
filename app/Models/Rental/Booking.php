<?php
namespace App\Models\Rental;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Booking extends Model
{
    protected $table = 'rental_bookings';
    protected $guarded = [];
    protected $casts = [
        'start_at' => 'datetime', 'end_at' => 'datetime',
        'actual_start_at' => 'datetime', 'actual_end_at' => 'datetime',
        'hold_expires_at' => 'datetime', 'computed_units' => 'float',
    ];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function bike(): BelongsTo { return $this->belongsTo(Bike::class); }
    public function pickupHub(): BelongsTo { return $this->belongsTo(Hub::class, 'pickup_hub_id'); }
    public function returnHub(): BelongsTo { return $this->belongsTo(Hub::class, 'return_hub_id'); }
    public function coupon(): BelongsTo { return $this->belongsTo(Coupon::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function refunds(): HasMany { return $this->hasMany(Refund::class); }
    public function statusHistory(): HasMany { return $this->hasMany(BookingStatusHistory::class); }
}
