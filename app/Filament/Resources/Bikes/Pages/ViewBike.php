<?php

namespace App\Filament\Resources\Bikes\Pages;

use App\Filament\Resources\Bikes\BikeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBike extends ViewRecord
{
    protected static string $resource = BikeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
