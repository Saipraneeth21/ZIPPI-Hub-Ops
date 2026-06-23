<?php

namespace App\Models\Rental;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentReport extends Model
{
    protected $table = 'rental_incident_reports';

    protected $guarded = [];

    protected $casts = [
        'incident_date' => 'date',
        'estimated_cost' => 'integer',
    ];

    public function bike(): BelongsTo
    {
        return $this->belongsTo(Bike::class, 'bike_id');
    }
}
