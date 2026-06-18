<?php
namespace App\Models\Rental;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class DeviceToken extends Model
{
    protected $table = 'device_tokens';
    protected $guarded = [];
    protected $casts = ['last_seen_at' => 'datetime'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
