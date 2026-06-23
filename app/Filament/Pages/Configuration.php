<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Read-only view of platform configuration (Admin-Dashboard §3.10). Values are
 * sourced from config/rental.php (env-driven). Making these editable would need
 * a DB-backed settings store — see the note rendered on the page.
 */
class Configuration extends Page
{
    protected string $view = 'filament.pages.configuration';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;


    protected static ?string $navigationLabel = 'Configuration';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    /** @return array<string, string> */
    public function getSettings(): array
    {
        $rupees = fn (int $paise) => '₹' . number_format($paise / 100, 2);

        return [
            'GST percent' => config('rental.gst_percent') . '%',
            'Platform fee' => $rupees((int) config('rental.platform_fee_paise')),
            'Checkout hold TTL' => config('rental.hold_ttl_minutes') . ' minutes',
            'Late penalty / hour' => $rupees((int) config('rental.late_penalty_per_hour_paise')),
            'Refund super-admin threshold' => $rupees((int) config('rental.refund_super_admin_threshold_paise')),
            'OTP length' => (string) config('rental.otp.length'),
            'OTP TTL' => config('rental.otp.ttl_seconds') . ' seconds',
            'Razorpay mode' => config('rental.razorpay.live_mode') ? 'Live' : 'Test',
        ];
    }

    /** @return array<int, array{lead: string, refund: string}> */
    public function getCancellationTiers(): array
    {
        return collect(config('rental.cancellation_tiers', []))
            ->map(fn ($t) => [
                'lead' => $t['min_hours_before'] . '+ hours before start',
                'refund' => (int) round($t['rental_refund_fraction'] * 100) . '% rental refund',
            ])
            ->all();
    }
}
