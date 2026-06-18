<?php

namespace App\Filament\Resources\GeofenceAlerts\Pages;

use App\Filament\Resources\GeofenceAlerts\GeofenceAlertResource;
use Filament\Resources\Pages\ListRecords;

class ListGeofenceAlerts extends ListRecords
{
    protected static string $resource = GeofenceAlertResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
