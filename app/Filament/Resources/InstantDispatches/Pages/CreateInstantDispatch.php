<?php

namespace App\Filament\Resources\InstantDispatches\Pages;

use App\Filament\Resources\InstantDispatches\InstantDispatchResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInstantDispatch extends CreateRecord
{
    protected static string $resource = InstantDispatchResource::class;

    // Only "Create" and "Cancel" — no "Create & create another".
    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
