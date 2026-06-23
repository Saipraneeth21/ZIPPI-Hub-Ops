<?php

namespace App\Filament\Resources\BikePricings\Pages;

use App\Filament\Resources\BikePricings\BikePricingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBikePricing extends CreateRecord
{
    protected static string $resource = BikePricingResource::class;

    // Only "Create" and "Cancel" — no "Create & create another".
    protected static bool $canCreateAnother = false;
}
