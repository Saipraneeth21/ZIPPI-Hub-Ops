<?php

namespace App\Filament\Resources\MaintenanceRecords\Pages;

use App\Filament\Resources\MaintenanceRecords\MaintenanceRecordResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMaintenanceRecord extends CreateRecord
{
    protected static string $resource = MaintenanceRecordResource::class;

    // Only "Create" and "Cancel" — no "Create & create another".
    protected static bool $canCreateAnother = false;
}
