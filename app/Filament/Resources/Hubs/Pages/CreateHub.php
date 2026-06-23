<?php

namespace App\Filament\Resources\Hubs\Pages;

use App\Filament\Resources\Hubs\HubResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHub extends CreateRecord
{
    protected static string $resource = HubResource::class;

    // Only "Create" and "Cancel" — no "Create & create another".
    protected static bool $canCreateAnother = false;
}
