<?php

namespace App\Filament\Hub\Resources\ActiveRentals\Pages;

use App\Filament\Hub\Resources\ActiveRentals\ActiveRentalResource;
use Filament\Resources\Pages\ListRecords;

class ListActiveRentals extends ListRecords
{
    protected static string $resource = ActiveRentalResource::class;
}
