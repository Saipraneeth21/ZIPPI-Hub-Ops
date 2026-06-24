<?php

namespace App\Models\Rental;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bike extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'rental_bikes';

    protected $guarded = [];

    protected $casts = ['specifications' => 'array'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(BikeCategory::class, 'category_id');
    }

    public function hub(): BelongsTo
    {
        return $this->belongsTo(Hub::class, 'hub_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(BikeImage::class);
    }

    public function pricing(): HasOne
    {
        return $this->hasOne(BikePricing::class)->where('is_active', true);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(BikePricing::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function telemetry(): HasMany
    {
        return $this->hasMany(BikeTelemetry::class);
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class, 'bike_id');
    }

    public function latestMaintenanceRecord(): HasOne
    {
        return $this->hasOne(MaintenanceRecord::class, 'bike_id')->latestOfMany();
    }

    /**
     * Bikes needing service attention: in Maintenance status, OR whose latest
     * maintenance record's next service is overdue / due within $dueSoonDays.
     * Mirrors the admin "Maintenance Due" worklist logic.
     */
    public function scopeMaintenanceDue(Builder $query, int $dueSoonDays = 14): Builder
    {
        return $query->where(function (Builder $q) use ($dueSoonDays) {
            $q->where('status', 'maintenance')
                ->orWhereHas('latestMaintenanceRecord', fn (Builder $r) => $r
                    ->whereNotNull('next_service_due')
                    ->whereDate('next_service_due', '<=', now()->addDays($dueSoonDays)));
        });
    }

    /** True when the bike has bookings that block deletion (active or upcoming). */
    public function hasBlockingBookings(): bool
    {
        return $this->bookings()
            ->whereIn('status', ['confirmed', 'active'])
            ->exists();
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(BikeImage::class)->where('is_primary', true);
    }
}
