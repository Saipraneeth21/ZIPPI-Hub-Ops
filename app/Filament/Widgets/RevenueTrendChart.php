<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Models\Rental\Payment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

/**
 * Revenue trend — captured payments per day for the last 14 days (§3.1).
 */
class RevenueTrendChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Revenue Trend (14 days)';

    protected ?string $pollingInterval = '60s';

    protected int | string | array $columnSpan = 'full';

    protected ?string $maxHeight = '320px';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $labels = [];
        $byDay = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $key = $date->toDateString();
            $byDay[$key] = 0;
            $labels[] = $date->format('d M');
        }

        $rows = Payment::where('status', PaymentStatus::Captured->value)
            ->where('paid_at', '>=', Carbon::now()->subDays(14)->startOfDay())
            ->get(['amount', 'paid_at']);

        foreach ($rows as $row) {
            $day = $row->paid_at?->toDateString();
            if ($day !== null && array_key_exists($day, $byDay)) {
                $byDay[$day] += $row->amount / 100; // paise -> rupees
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (₹)',
                    'data' => array_values($byDay),
                    'borderColor' => '#40E0D0', // ZIPPI turquoise
                    'backgroundColor' => 'rgba(64, 224, 208, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
