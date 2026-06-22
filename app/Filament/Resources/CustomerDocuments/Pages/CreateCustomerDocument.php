<?php

namespace App\Filament\Resources\CustomerDocuments\Pages;

use App\Filament\Resources\CustomerDocuments\CustomerDocumentResource;
use App\Filament\Resources\CustomerDocuments\Pages\Concerns\StampsVerificationDate;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerDocument extends CreateRecord
{
    use StampsVerificationDate;

    protected static string $resource = CustomerDocumentResource::class;

    // Only "Create" and "Cancel" — no "Create & create another".
    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->stampVerificationDate($data);
    }

    /** After saving, return to a fresh, blank create form. */
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('create');
    }
}
