<?php
namespace App\Models\Rental;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Notification extends Model
{
    protected $table = 'rental_notifications';
    protected $guarded = [];
    protected $casts = ['data' => 'array', 'read_at' => 'datetime'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
