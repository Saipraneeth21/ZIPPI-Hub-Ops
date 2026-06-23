<?php
namespace App\Models\Rental;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Hub extends Model
{
    use HasFactory;
    protected $table = 'rental_hubs';
    protected $guarded = [];
    protected $casts = ['geofence_polygon' => 'array', 'is_active' => 'boolean', 'latitude' => 'float', 'longitude' => 'float'];
    public function city(): BelongsTo { return $this->belongsTo(City::class, 'city_id'); }
    public function bikes(): HasMany { return $this->hasMany(Bike::class, 'hub_id'); }
}
