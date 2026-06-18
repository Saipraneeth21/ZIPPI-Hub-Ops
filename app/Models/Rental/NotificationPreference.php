<?php
namespace App\Models\Rental;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class NotificationPreference extends Model
{
    protected $table = 'notification_preferences';
    protected $guarded = [];
    protected $casts = [
        'push_transactional' => 'boolean', 'push_promotional' => 'boolean',
        'sms_promotional' => 'boolean', 'email_promotional' => 'boolean',
    ];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
