<?php

namespace App\Models\Rental;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Hub Operations: pre-handover capture for a booking pickup. */
class HubHandover extends Model
{
    protected $table = 'hub_handovers';

    protected $guarded = [];

    protected $casts = [
        'battery_percent' => 'integer',
        'checklist' => 'array',
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
