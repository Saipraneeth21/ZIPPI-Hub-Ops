<?php

namespace App\Filament\Resources\Hubs\Pages;

use App\Filament\Resources\Hubs\HubResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHubs extends ListRecords
{
    protected static string $resource = HubResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
