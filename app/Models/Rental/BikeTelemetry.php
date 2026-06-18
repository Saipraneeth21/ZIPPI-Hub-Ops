<?php
namespace App\Models\Rental;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class BikeTelemetry extends Model
{
    protected $table = 'rental_bike_telemetry';
    protected $guarded = [];
    protected $casts = ['recorded_at' => 'datetime', 'ignition' => 'boolean', 'latitude' => 'float', 'longitude' => 'float'];
    public function bike(): BelongsTo { return $this->belongsTo(Bike::class); }
}
