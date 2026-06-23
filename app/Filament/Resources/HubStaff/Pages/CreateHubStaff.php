<?php

namespace App\Filament\Resources\HubStaff\Pages;

use App\Filament\Resources\HubStaff\HubStaffResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHubStaff extends CreateRecord
{
    protected static string $resource = HubStaffResource::class;

    // Only "Create" and "Cancel" — no "Create & create another".
    protected static bool $canCreateAnother = false;

    /**
     * After saving, reopen a blank create form (instead of the edit page) so the
     * previous entry's data is cleared and the next staff member can be added.
     */
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('create');
    }
}
