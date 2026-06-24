<?php

namespace App\Filament\Hub\Resources\Pickups\Pages;

use App\Filament\Hub\Resources\Pickups\PickupResource;
use Filament\Resources\Pages\ListRecords;

class ListPickups extends ListRecords
{
    protected static string $resource = PickupResource::class;
}
