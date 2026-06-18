<?php
namespace App\Models\Rental;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class BikeCategory extends Model
{
    use HasFactory;
    protected $table = 'rental_bike_categories';
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];
    public function bikes(): HasMany { return $this->hasMany(Bike::class, 'category_id'); }
}
