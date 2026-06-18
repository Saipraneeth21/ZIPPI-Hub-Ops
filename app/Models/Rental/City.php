<?php
namespace App\Models\Rental;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class City extends Model
{
    use HasFactory;
    protected $table = 'cities';
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];
    public function hubs(): HasMany { return $this->hasMany(Hub::class); }
}
