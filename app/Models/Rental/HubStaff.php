<?php

namespace App\Models\Rental;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * On-site hub staff account. Managed in the admin panel (HubStaffResource) and,
 * as of Hub Operations, also an auth identity: it issues Sanctum tokens for the
 * Hub Ops API and signs in to the dedicated /hub Filament panel.
 *
 * Extends Authenticatable (which extends Model) so existing admin CRUD keeps
 * working unchanged while gaining login capability.
 */
class HubStaff extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'hub_staff';

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    public function hub(): BelongsTo
    {
        return $this->belongsTo(Hub::class, 'hub_id');
    }

    /** Only active staff may sign in, and only to the hub panel. */
    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'hub' && (bool) $this->is_active;
    }
}
