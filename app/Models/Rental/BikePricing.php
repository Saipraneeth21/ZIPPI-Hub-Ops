<?php
namespace App\Models\Rental;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class BikePricing extends Model
{
    use HasFactory;
    protected $table = 'rental_bike_pricing';
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean', 'effective_from' => 'date', 'effective_to' => 'date'];
    public function bike(): BelongsTo { return $this->belongsTo(Bike::class); }
}
