<?php
namespace App\Models\Rental;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class RecentSearch extends Model
{
    protected $table = 'rental_recent_searches';
    protected $guarded = [];
    protected $casts = ['search_payload' => 'array'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
