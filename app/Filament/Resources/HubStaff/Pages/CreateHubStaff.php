<?php

namespace App\Filament\Resources\HubStaff\Pages;

use App\Filament\Resources\HubStaff\HubStaffResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHubStaff extends CreateRecord
{
    protected static string $resource = HubStaffResource::class;

    /** Keep only "Create" and "Cancel" — drop "Create & create another". */
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    /**
     * After saving, reopen a blank create form (instead of the edit page) so the
     * previous entry's data is cleared and the next staff member can be added.
     */
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('create');
    }
}
