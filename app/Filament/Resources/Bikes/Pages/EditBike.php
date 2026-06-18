<?php

namespace App\Filament\Resources\Bikes\Pages;

use App\Filament\Resources\Bikes\BikeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBike extends EditRecord
{
    protected static string $resource = BikeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
