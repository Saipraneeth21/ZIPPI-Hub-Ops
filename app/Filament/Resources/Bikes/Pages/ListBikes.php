<?php

namespace App\Filament\Resources\Bikes\Pages;

use App\Enums\BikeStatus;
use App\Filament\Resources\Bikes\BikeResource;
use App\Filament\Resources\Bikes\Support\ImportBikesAction;
use Filament\Actions\CreateAction;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListBikes extends ListRecords
{
    protected static string $resource = BikeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportBikesAction::template(),
            ImportBikesAction::make(),
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'available' => Tab::make('Available')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', BikeStatus::Available->value)),
            'maintenance' => Tab::make('Maintenance')
                ->icon('heroicon-m-wrench-screwdriver')
                ->badge(fn () => BikeResource::getModel()::query()->where('status', BikeStatus::Maintenance->value)->count() ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', BikeStatus::Maintenance->value)),
            'inactive' => Tab::make('Inactive')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', BikeStatus::Inactive->value)),
        ];
    }
}
