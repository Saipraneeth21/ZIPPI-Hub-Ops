<?php

namespace App\Filament\Resources\InstantDispatches\Pages;

use App\Filament\Resources\InstantDispatches\InstantDispatchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInstantDispatches extends ListRecords
{
    protected static string $resource = InstantDispatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
