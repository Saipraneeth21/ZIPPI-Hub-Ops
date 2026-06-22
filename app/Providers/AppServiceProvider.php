<?php

namespace App\Providers;

use App\Models\Rental\AdminLoginActivity;
use App\Models\Rental\AdminUser;
use App\Support\Rental\AdminAccess;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerAdminGates();
        $this->recordAdminLoginActivity();
    }

    /**
     * Record login / logout / failed authentication events for the admin guard
     * into the login-activity trail.
     */
    private function recordAdminLoginActivity(): void
    {
        $log = function (string $event, ?AdminUser $admin, ?string $email) {
            AdminLoginActivity::create([
                'admin_user_id' => $admin?->getKey(),
                'email' => $admin?->email ?? $email,
                'event' => $event,
                'ip_address' => Request::ip(),
                'user_agent' => substr((string) Request::userAgent(), 0, 255),
            ]);
        };

        Event::listen(function (Login $e) use ($log) {
            if ($e->guard === 'admin' && $e->user instanceof AdminUser) {
                $log('login', $e->user, null);
            }
        });

        Event::listen(function (Logout $e) use ($log) {
            if ($e->guard === 'admin' && $e->user instanceof AdminUser) {
                $log('logout', $e->user, null);
            }
        });

        Event::listen(function (Failed $e) use ($log) {
            if ($e->guard === 'admin') {
                $log('failed', $e->user instanceof AdminUser ? $e->user : null, $e->credentials['email'] ?? null);
            }
        });
    }

    /**
     * Register the Admin Dashboard RBAC abilities as Gates. super_admin is
     * granted everything via the before() hook; all other roles are checked
     * against the AdminAccess matrix.
     */
    private function registerAdminGates(): void
    {
        Gate::before(function ($user, string $ability) {
            // Full-access roles (super_admin, admin, developer, manager) bypass
            // the matrix and are granted every ability.
            if ($user instanceof AdminUser && AdminAccess::hasFullAccess($user)) {
                return true;
            }

            return null; // fall through to the specific ability check
        });

        foreach (AdminAccess::abilities() as $ability) {
            Gate::define($ability, fn (AdminUser $user) => AdminAccess::allows($ability, $user));
        }
    }
}
