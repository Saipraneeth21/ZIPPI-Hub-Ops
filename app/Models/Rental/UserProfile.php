<?php
namespace App\Models\Rental;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class UserProfile extends Model
{
    protected $table = 'rental_user_profiles';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $guarded = [];
    protected $casts = ['is_blocked' => 'boolean'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
