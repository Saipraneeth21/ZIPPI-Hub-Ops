<?php

namespace App\Filament\Resources\CustomerDocuments\Pages;

use App\Filament\Resources\CustomerDocuments\CustomerDocumentResource;
use App\Filament\Resources\CustomerDocuments\Pages\Concerns\StampsVerificationDate;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerDocument extends CreateRecord
{
    use StampsVerificationDate;

    protected static string $resource = CustomerDocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->stampVerificationDate($data);
    }
}
