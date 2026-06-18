<?php
namespace App\Models\Rental;

use App\Enums\AdminRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class AdminUser extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory;

    protected $table = 'admin_users';
    protected $guarded = [];
    protected $hidden = ['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'];
    protected $casts = [
        'is_active' => 'boolean',
        'password' => 'hashed',
        'role' => AdminRole::class,
    ];

    /** Only active admins may reach the Filament panel. */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active === true;
    }

    /** RBAC helper: does this admin hold any of the given roles? */
    public function hasRole(AdminRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === AdminRole::SuperAdmin;
    }
}
