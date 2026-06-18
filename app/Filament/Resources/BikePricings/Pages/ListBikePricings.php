<?php

namespace App\Filament\Resources\BikePricings\Pages;

use App\Filament\Resources\BikePricings\BikePricingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBikePricings extends ListRecords
{
    protected static string $resource = BikePricingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
