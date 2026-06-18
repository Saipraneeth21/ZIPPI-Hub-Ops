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
 * super_admin is implicitly allowed everything (see AppServiceProvider::before()).
 */
final class AdminAccess
{
    /** @var array<string, array<int, AdminRole>> ability => allowed roles */
    public const MATRIX = [
        // Dashboard / analytics — everyone can view.
        'dashboard.view' => [AdminRole::SuperAdmin, AdminRole::Ops, AdminRole::KycReviewer, AdminRole::Support],

        // Users
        'users.view'     => [AdminRole::SuperAdmin, AdminRole::Ops, AdminRole::KycReviewer, AdminRole::Support],
        'users.block'    => [AdminRole::SuperAdmin, AdminRole::Ops],

        // KYC
        'kyc.review'     => [AdminRole::SuperAdmin, AdminRole::KycReviewer],

        // Fleet / pricing
        'bikes.manage'   => [AdminRole::SuperAdmin, AdminRole::Ops],
        'pricing.manage' => [AdminRole::SuperAdmin, AdminRole::Ops],

        // Orders
        'orders.view'    => [AdminRole::SuperAdmin, AdminRole::Ops, AdminRole::Support],
        'orders.manage'  => [AdminRole::SuperAdmin, AdminRole::Ops], // cancel / refund / force-return

        // Instant Dispatch (walk-in counter)
        'instant_dispatch.manage' => [AdminRole::SuperAdmin, AdminRole::Ops],

        // Payments & wallet
        'payments.view'  => [AdminRole::SuperAdmin, AdminRole::Ops, AdminRole::Support],
        'refunds.manage' => [AdminRole::SuperAdmin, AdminRole::Ops],
        'wallet.adjust'  => [AdminRole::SuperAdmin],

        // Tracking
        'tracking.view'  => [AdminRole::SuperAdmin, AdminRole::Ops, AdminRole::Support],
        'tracking.manage' => [AdminRole::SuperAdmin, AdminRole::Ops],

        // Marketing & reviews
        'marketing.manage' => [AdminRole::SuperAdmin, AdminRole::Ops],
        'reviews.manage'   => [AdminRole::SuperAdmin, AdminRole::Ops],

        // Platform administration (super_admin only)
        'settings.manage' => [AdminRole::SuperAdmin],
        'audit.view'     => [AdminRole::SuperAdmin],
    ];

    /** All ability keys, for bulk Gate registration. */
    public static function abilities(): array
    {
        return array_keys(self::MATRIX);
    }

    public static function allows(string $ability, AdminUser $admin): bool
    {
        $roles = self::MATRIX[$ability] ?? [];

        return in_array($admin->role, $roles, true);
    }
}
