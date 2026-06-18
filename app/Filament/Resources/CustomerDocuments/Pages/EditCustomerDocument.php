<?php

namespace App\Filament\Resources\CustomerDocuments\Pages;

use App\Filament\Resources\CustomerDocuments\CustomerDocumentResource;
use App\Filament\Resources\CustomerDocuments\Pages\Concerns\StampsVerificationDate;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomerDocument extends EditRecord
{
    use StampsVerificationDate;

    protected static string $resource = CustomerDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->stampVerificationDate($data);
    }
}
