<?php

namespace App\Filament\Resources\InstantDispatches\Pages;

use App\Filament\Resources\InstantDispatches\InstantDispatchResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewInstantDispatch extends ViewRecord
{
    protected static string $resource = InstantDispatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
