<?php

namespace App\Models\Rental;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Hub Operations: return capture for a booking completion. */
class HubReturn extends Model
{
    protected $table = 'hub_returns';

    protected $guarded = [];

    protected $casts = [
        'odometer_reading' => 'integer',
        'battery_percent' => 'integer',
        'photos' => 'array',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(HubStaff::class, 'hub_staff_id');
    }
}
