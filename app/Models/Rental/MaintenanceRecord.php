<?php

namespace App\Models\Rental;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRecord extends Model
{
    protected $table = 'rental_maintenance_records';

    protected $guarded = [];

    protected $casts = [
        'maintenance_date' => 'date',
        'next_service_due' => 'date',
        'cost' => 'integer',
        'odometer_reading' => 'integer',
        'attachments' => 'array',
    ];

    public function bike(): BelongsTo
    {
        return $this->belongsTo(Bike::class, 'bike_id');
    }
}
