<?php

namespace App\Filament\Resources\InstantDispatches\Pages;

use App\Filament\Resources\InstantDispatches\InstantDispatchResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditInstantDispatch extends EditRecord
{
    protected static string $resource = InstantDispatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
