<?php
namespace App\Models\Rental;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class BikeImage extends Model
{
    protected $table = 'rental_bike_images';
    protected $guarded = [];
    protected $casts = ['is_primary' => 'boolean'];
    public function bike(): BelongsTo { return $this->belongsTo(Bike::class); }
}
