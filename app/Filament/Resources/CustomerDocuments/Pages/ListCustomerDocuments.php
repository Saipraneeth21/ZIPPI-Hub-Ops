<?php

namespace App\Filament\Resources\CustomerDocuments\Pages;

use App\Filament\Resources\CustomerDocuments\CustomerDocumentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomerDocuments extends ListRecords
{
    protected static string $resource = CustomerDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
