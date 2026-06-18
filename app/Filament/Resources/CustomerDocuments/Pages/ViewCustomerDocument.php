<?php

namespace App\Filament\Resources\CustomerDocuments\Pages;

use App\Filament\Resources\CustomerDocuments\CustomerDocumentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerDocument extends ViewRecord
{
    protected static string $resource = CustomerDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
