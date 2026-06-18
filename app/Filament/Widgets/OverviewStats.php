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

    protected function getStats(): array
    {
        $since = Carbon::now()->subDays(30);

        // Revenue: captured payments in the last 30 days (stored in paise).
        $revenuePaise = (int) Payment::where('status', PaymentStatus::Captured->value)
            ->where('paid_at', '>=', $since)
            ->sum('amount');

        $activeRentals = Booking::where('status', BookingStatus::Active->value)->count();
        $completedRentals = Booking::where('status', BookingStatus::Completed->value)
            ->where('created_at', '>=', $since)
            ->count();

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
                ->color('success')
                ->chart($this->revenueSparkline($since))
                ->url($this->link('payments.view', fn () => PaymentResource::getUrl())),

            Stat::make('Active Rentals', (string) $activeRentals)
                ->description('Bikes out right now')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('warning')
                ->url($this->link('orders.view', fn () => BookingResource::getUrl('index', ['activeTab' => 'active']))),

            Stat::make('Completed (30d)', (string) $completedRentals)
                ->description('Finished rentals')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->url($this->link('orders.view', fn () => BookingResource::getUrl('index', ['activeTab' => 'completed']))),

            Stat::make('Fleet Utilization', $utilization . '%')
                ->description($bookedBikes . ' of ' . $serviceableBikes . ' bikes booked')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($utilization >= 70 ? 'success' : 'gray')
                ->url($this->link('bikes.manage', fn () => BikeResource::getUrl())),

            Stat::make('KYC Approval Rate', $approvalRate . '%')
                ->description($pendingKyc . ' pending review')
                ->descriptionIcon('heroicon-m-identification')
                ->color($pendingKyc > 0 ? 'warning' : 'success')
                ->url($this->link('kyc.review', fn () => KycResource::getUrl())),

            Stat::make('New Users (30d)', (string) $newUsers)
                ->description('Registrations')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('info')
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
