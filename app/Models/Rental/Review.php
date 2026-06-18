<?php
namespace App\Models\Rental;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $table = 'rental_reviews';
    protected $guarded = [];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function bike(): BelongsTo { return $this->belongsTo(Bike::class); }
    public function booking(): BelongsTo { return $this->belongsTo(Booking::class); }
}
