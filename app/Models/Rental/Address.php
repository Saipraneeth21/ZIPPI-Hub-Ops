<?php
namespace App\Models\Rental;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Address extends Model
{
    protected $table = 'rental_addresses';
    protected $guarded = [];
    protected $casts = ['is_default' => 'boolean', 'latitude' => 'float', 'longitude' => 'float'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
