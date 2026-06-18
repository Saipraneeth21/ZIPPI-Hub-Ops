<?php

namespace App\Filament\Resources\Hubs\Pages;

use App\Filament\Resources\Hubs\HubResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHub extends EditRecord
{
    protected static string $resource = HubResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
