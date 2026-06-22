<?php

namespace App\Support\Rental;

use App\Enums\AdminRole;
use App\Models\Rental\AdminUser;

/**
 * Single source of truth for the Admin Dashboard RBAC matrix.
 * Mirrors Admin-Dashboard/01-Admin-Modules.md §2. Each ability maps to the
 * set of roles allowed to perform it. Registered as Gates in AppServiceProvider
 * so Filament resources/pages can call `Gate::allows('bikes.manage')` or
 * `$user->can('kyc.review')` for navigation + action visibility.
 *
 * Full-access roles (see FULL_ACCESS_ROLES) are implicitly allowed everything
 * via AppServiceProvider::before(); they are NOT listed in the matrix below.
 */
final class AdminAccess
{
    /**
     * Roles with unrestricted access — every ability passes for them via the
     * Gate::before() hook in AppServiceProvider. Admin / Developer / Manager
     * get the same blanket access as the original super_admin.
     *
     * @var array<int, AdminRole>
     */
    public const FULL_ACCESS_ROLES = [
        AdminRole::SuperAdmin,
        AdminRole::Admin,
        AdminRole::Developer,
        AdminRole::Manager,
    ];

    /**
     * ability => roles allowed (in addition to the full-access roles above).
     *
     * Limited roles (Supervisor / Telecaller / Marketer) may ONLY: view the
     * dashboard, view users, and accept instant dispatches.
     *
     * @var array<string, array<int, AdminRole>>
     */
    public const MATRIX = [
        // Dashboard / analytics.
        'dashboard.view' => [AdminRole::Ops, AdminRole::KycReviewer, AdminRole::Support, AdminRole::Supervisor, AdminRole::Telecaller, AdminRole::Marketer],

        // Users
        'users.view'     => [AdminRole::Ops, AdminRole::KycReviewer, AdminRole::Support, AdminRole::Supervisor, AdminRole::Telecaller, AdminRole::Marketer],
        'users.block'    => [AdminRole::Ops],
        'users.manage'   => [AdminRole::Ops], // create / edit riders from admin

        // KYC
        'kyc.review'     => [AdminRole::KycReviewer],

        // Fleet / pricing
        'bikes.manage'   => [AdminRole::Ops],
        'pricing.manage' => [AdminRole::Ops],

        // Orders — limited roles get read-only view (Bookings by Status cards
        // are clickable and the Orders list is viewable), but not manage.
        'orders.view'    => [AdminRole::Ops, AdminRole::Support, AdminRole::Supervisor, AdminRole::Telecaller, AdminRole::Marketer],
        'orders.manage'  => [AdminRole::Ops], // cancel / refund / force-return

        // Instant Dispatch (walk-in counter)
        'instant_dispatch.manage' => [AdminRole::Ops, AdminRole::Supervisor, AdminRole::Telecaller, AdminRole::Marketer],

        // Payments & wallet
        'payments.view'  => [AdminRole::Ops, AdminRole::Support],
        'refunds.manage' => [AdminRole::Ops],
        'wallet.adjust'  => [],

        // Tracking
        'tracking.view'  => [AdminRole::Ops, AdminRole::Support],
        'tracking.manage' => [AdminRole::Ops],

        // Marketing & reviews
        'marketing.manage' => [AdminRole::Ops],
        'reviews.manage'   => [AdminRole::Ops],

        // Platform administration (full-access roles only)
        'settings.manage' => [],
        'audit.view'     => [],
    ];

    /** All ability keys, for bulk Gate registration. */
    public static function abilities(): array
    {
        return array_keys(self::MATRIX);
    }

    /** True for roles that bypass the matrix entirely (granted everything). */
    public static function hasFullAccess(AdminUser $admin): bool
    {
        return in_array($admin->role, self::FULL_ACCESS_ROLES, true);
    }

    public static function allows(string $ability, AdminUser $admin): bool
    {
        if (self::hasFullAccess($admin)) {
            return true;
        }

        $roles = self::MATRIX[$ability] ?? [];

        return in_array($admin->role, $roles, true);
    }
}
