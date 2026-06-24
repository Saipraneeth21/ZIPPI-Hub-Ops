<?php

namespace App\Filament\Resources\MaintenanceDue\Pages;

use App\Filament\Resources\MaintenanceDue\MaintenanceDueResource;
use Filament\Resources\Pages\ListRecords;

class ListMaintenanceDue extends ListRecords
{
    protected static string $resource = MaintenanceDueResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
