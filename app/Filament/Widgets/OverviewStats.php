<?php

namespace App\Filament\Widgets;

use App\Enums\BikeStatus;
use App\Enums\BookingStatus;
use App\Enums\KycStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Bikes\BikeResource;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Kyc\KycResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Rental\Bike;
use App\Models\Rental\Booking;
use App\Models\Rental\Payment;
use App\Models\Rental\UserProfile;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

/**
 * Dashboard KPI cards — Admin-Dashboard/01-Admin-Modules.md §3.1.
 * Revenue / completed / active rentals, fleet utilization, KYC approval rate,
 * new users. Polls every 30s so the operational counters stay live.
 */
class OverviewStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '30s';

    protected ?string $heading = 'Operational Overview';

    /** Selected period for the Completed card: '30d' or a 'Y-m' month key. */
    public string $completedPeriod = '30d';

    public function selectCompletedPeriod(string $period): void
    {
        $this->completedPeriod = $period;
    }

    protected function getStats(): array
    {
        $since = Carbon::now()->subDays(30);

        // Revenue: captured payments in the last 30 days (stored in paise).
        $revenuePaise = (int) Payment::where('status', PaymentStatus::Captured->value)
            ->where('paid_at', '>=', $since)
            ->sum('amount');

        $activeRentals = Booking::where('status', BookingStatus::Active->value)->count();

        // Completed: a clickable period selector (last 30 days + last 3 months).
        // Clicking a line sets $completedPeriod, which updates the figure below.
        $periods = [[
            'key' => '30d',
            'label' => 'Last 30 days',
            'short' => '30d',
            'count' => Booking::where('status', BookingStatus::Completed->value)
                ->where('created_at', '>=', $since)
                ->count(),
        ]];
        for ($i = 2; $i >= 0; $i--) {
            $start = Carbon::now()->startOfMonth()->subMonths($i);
            $end = (clone $start)->endOfMonth();
            $periods[] = [
                'key' => $start->format('Y-m'),
                'label' => $start->format('F'),
                'short' => $start->format('M'),
                'count' => Booking::where('status', BookingStatus::Completed->value)
                    ->whereBetween('created_at', [$start, $end])
                    ->count(),
            ];
        }

        $selected = collect($periods)->firstWhere('key', $this->completedPeriod) ?? $periods[0];
        $completedRentals = $selected['count'];
        $completedTitle = 'Completed (' . $selected['short'] . ')';

        $lines = ['Finished rentals'];
        foreach ($periods as $period) {
            $isActive = $period['key'] === $this->completedPeriod;
            $style = 'cursor:pointer;' . ($isActive ? 'font-weight:700;text-decoration:underline;' : '');
            // .prevent.stop: change the figure only; do NOT follow the card's link.
            $lines[] = '<span wire:click.prevent.stop="selectCompletedPeriod(\'' . $period['key'] . '\')" style="' . $style . '">'
                . e($period['label']) . ' · ' . $period['count'] . '</span>';
        }
        $completedDescription = new HtmlString(implode('<br>', $lines));

        // Fleet utilization: bikes currently booked / fleet excluding inactive.
        $bookedBikes = Bike::where('status', BikeStatus::Booked->value)->count();
        $serviceableBikes = Bike::where('status', '!=', BikeStatus::Inactive->value)->count();
        $utilization = $serviceableBikes > 0
            ? round($bookedBikes / $serviceableBikes * 100, 1)
            : 0.0;

        // KYC approval rate: approved / decided (approved + rejected).
        $approved = UserProfile::where('kyc_status', KycStatus::Approved->value)->count();
        $rejected = UserProfile::where('kyc_status', KycStatus::Rejected->value)->count();
        $decided = $approved + $rejected;
        $approvalRate = $decided > 0 ? round($approved / $decided * 100, 1) : 0.0;
        $pendingKyc = UserProfile::whereIn('kyc_status', [
            KycStatus::Pending->value,
            KycStatus::UnderReview->value,
        ])->count();

        $newUsers = User::where('created_at', '>=', $since)->count();

        return [
            Stat::make('Revenue (30d)', '₹' . number_format($revenuePaise / 100, 2))
                ->description('Captured payments')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary')
                ->chart($this->revenueSparkline($since))
                ->url($this->link('payments.view', fn () => PaymentResource::getUrl())),

            Stat::make('Active Rentals', (string) $activeRentals)
                ->description('Bikes out right now')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('warning')
                ->url($this->link('orders.view', fn () => BookingResource::getUrl('index', ['activeTab' => 'active']))),

            Stat::make($completedTitle, (string) $completedRentals)
                // No descriptionIcon (it misaligns against the multi-line list).
                // Clicking the card opens Bookings; the month lines use
                // wire:click.prevent.stop so they only change the figure.
                ->description($completedDescription)
                ->color('primary')
                ->url($this->link('orders.view', fn () => BookingResource::getUrl('index', ['activeTab' => 'completed']))),

            Stat::make('Fleet Utilization', $utilization . '%')
                ->description($bookedBikes . ' of ' . $serviceableBikes . ' bikes booked')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($utilization >= 70 ? 'primary' : 'gray')
                ->url($this->link('bikes.manage', fn () => BikeResource::getUrl())),

            Stat::make('KYC Approval Rate', $approvalRate . '%')
                ->description($pendingKyc . ' pending review')
                ->descriptionIcon('heroicon-m-identification')
                ->color($pendingKyc > 0 ? 'warning' : 'success')
                ->url($this->link('kyc.review', fn () => KycResource::getUrl())),

            Stat::make('New Users (30d)', (string) $newUsers)
                ->description('Registrations')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('primary')
                ->url($this->link('users.view', fn () => UserResource::getUrl())),
        ];
    }

    /** Build a card link only when the viewer can access the destination. */
    private function link(string $ability, callable $urlFactory): ?string
    {
        return (auth()->user()?->can($ability) ?? false) ? $urlFactory() : null;
    }

    /** Daily captured-revenue (in rupees) for the last 7 days, for the card sparkline. */
    private function revenueSparkline(Carbon $since): array
    {
        $rows = Payment::where('status', PaymentStatus::Captured->value)
            ->where('paid_at', '>=', Carbon::now()->subDays(7))
            ->get(['amount', 'paid_at']);

        $byDay = [];
        for ($i = 6; $i >= 0; $i--) {
            $byDay[Carbon::now()->subDays($i)->toDateString()] = 0;
        }
        foreach ($rows as $row) {
            $day = $row->paid_at?->toDateString();
            if ($day !== null && array_key_exists($day, $byDay)) {
                $byDay[$day] += $row->amount / 100;
            }
        }

        return array_values($byDay);
    }
}
