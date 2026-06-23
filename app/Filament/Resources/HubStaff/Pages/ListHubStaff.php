<?php

namespace App\Filament\Resources\HubStaff\Pages;

use App\Filament\Resources\HubStaff\HubStaffResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHubStaff extends ListRecords
{
    protected static string $resource = HubStaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New hub staff'),
        ];
    }
}
