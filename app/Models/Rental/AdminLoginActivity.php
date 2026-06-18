<?php

namespace App\Models\Rental;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Security trail of admin authentication events (login / logout / failed),
 * recorded by listeners on the `admin` guard. See AppServiceProvider.
 */
class AdminLoginActivity extends Model
{
    protected $table = 'admin_login_activities';
    protected $guarded = [];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id');
    }
}
