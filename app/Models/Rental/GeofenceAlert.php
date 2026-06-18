<?php
namespace App\Models\Rental;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeofenceAlert extends Model
{
    protected $table = 'rental_geofence_alerts';
    protected $guarded = [];
    protected $casts = ['resolved' => 'boolean', 'latitude' => 'float', 'longitude' => 'float'];

    public function bike(): BelongsTo { return $this->belongsTo(Bike::class); }
    public function booking(): BelongsTo { return $this->belongsTo(Booking::class); }
}
