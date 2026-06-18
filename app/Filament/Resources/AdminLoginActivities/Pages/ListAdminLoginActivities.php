<?php

namespace App\Filament\Resources\AdminLoginActivities\Pages;

use App\Filament\Resources\AdminLoginActivities\AdminLoginActivityResource;
use Filament\Resources\Pages\ListRecords;

class ListAdminLoginActivities extends ListRecords
{
    protected static string $resource = AdminLoginActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
