<?php

namespace App\Filament\Resources\BikePricings\Pages;

use App\Filament\Resources\BikePricings\BikePricingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBikePricing extends EditRecord
{
    protected static string $resource = BikePricingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
