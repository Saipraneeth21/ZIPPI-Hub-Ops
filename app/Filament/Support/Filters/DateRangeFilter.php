<?php

namespace App\Filament\Support\Filters;

use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Reusable "From / To" date-range filter for resource tables.
 * Filters on a date column (default created_at) and shows active indicators.
 * Add to any table via: DateRangeFilter::make() inside ->filters([...]).
 */
class DateRangeFilter
{
    public static function make(string $column = 'created_at', string $label = 'Date'): Filter
    {
        return Filter::make($column)
            ->label($label)
            ->columns(2)
            ->schema([
                DatePicker::make('from')->label('From'),
                DatePicker::make('until')->label('To'),
            ])
            ->query(fn (Builder $query, array $data) => $query
                ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate($column, '>=', $d))
                ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate($column, '<=', $d)))
            ->indicateUsing(function (array $data) use ($label): array {
                $indicators = [];
                if ($data['from'] ?? null) {
                    $indicators[] = $label . ' from ' . Carbon::parse($data['from'])->format('d M Y');
                }
                if ($data['until'] ?? null) {
                    $indicators[] = $label . ' to ' . Carbon::parse($data['until'])->format('d M Y');
                }

                return $indicators;
            });
    }
}
