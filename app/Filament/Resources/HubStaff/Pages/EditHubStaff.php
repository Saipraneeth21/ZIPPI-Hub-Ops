<?php

namespace App\Filament\Resources\HubStaff\Pages;

use App\Filament\Resources\HubStaff\HubStaffResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHubStaff extends EditRecord
{
    protected static string $resource = HubStaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
