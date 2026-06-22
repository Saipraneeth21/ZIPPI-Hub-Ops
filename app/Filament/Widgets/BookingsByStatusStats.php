<?php

namespace App\Filament\Widgets;

use App\Enums\BookingStatus;
use App\Enums\RefundStatus;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Refunds\RefundResource;
use App\Models\Rental\Booking;
use App\Models\Rental\Refund;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Bookings broken down by lifecycle status as clickable stat blocks (§3.1).
 * Each block deep-links into the Orders list filtered to that status.
 */
class BookingsByStatusStats extends StatsOverviewWidget
{
    protected static ?int $sort = 5;

    protected ?string $pollingInterval = '30s';

    protected ?string $heading = 'Bookings by Status';

    /**
     * status => [label, colour, icon]. All cards use the ZIPPI brand turquoise
     * (primary); distinct icons keep each status differentiable.
     */
    private const STATUSES = [
        BookingStatus::Pending->value => ['Pending', 'primary', 'heroicon-m-clock'],
        BookingStatus::Confirmed->value => ['Confirmed', 'primary', 'heroicon-m-check'],
        BookingStatus::Active->value => ['Active', 'primary', 'heroicon-m-bolt'],
        BookingStatus::Completed->value => ['Completed', 'primary', 'heroicon-m-check-circle'],
        BookingStatus::Cancelled->value => ['Cancelled', 'primary', 'heroicon-m-x-circle'],
        BookingStatus::Expired->value => ['Expired', 'primary', 'heroicon-m-no-symbol'],
    ];

    protected function getStats(): array
    {
        $counts = Booking::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $canView = auth()->user()?->can('orders.view') ?? false;

        $stats = [];
        foreach (self::STATUSES as $status => [$label, $color, $icon]) {
            $count = (int) ($counts[$status] ?? 0);

            $stats[] = Stat::make($label, (string) $count)
                ->description('Bookings')
                ->descriptionIcon($icon)
                ->color($color)
                ->url($canView
                    ? BookingResource::getUrl('index', ['tableFilters' => ['status' => ['value' => $status]]])
                    : null);
        }

        // Refunds: how many customers have asked for a refund, plus how many are
        // still pending. Clicking opens the Refunds list for the full details.
        $refundTotal = Refund::count();
        $refundPending = Refund::where('status', RefundStatus::Pending->value)->count();
        $canViewRefunds = auth()->user()?->can('payments.view') ?? false;

        $stats[] = Stat::make('Refund', (string) $refundTotal)
            ->description($refundPending . ' pending')
            ->descriptionIcon('heroicon-m-receipt-refund')
            ->color('primary')
            ->url($canViewRefunds ? RefundResource::getUrl('index') : null);

        return $stats;
    }
}
