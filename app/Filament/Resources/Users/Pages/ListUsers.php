<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\CustomerDocuments\CustomerDocumentResource;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Pages\Dashboard;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Soft, rounded "Back" button matching the table's native Filter button.
            Action::make('back')
                ->label('Back')
                ->icon('heroicon-m-arrow-left')
                ->color('gray')
                ->url(Dashboard::getUrl()),

            CreateAction::make()
                ->label('New user')
                ->createAnother(false)
                ->modalWidth(Width::SevenExtraLarge)
                // After creating, go to the Documents section with the new user preselected.
                ->successRedirectUrl(fn (?Model $record): string => $record
                    ? CustomerDocumentResource::getUrl('create', ['user_id' => $record->getKey()])
                    : CustomerDocumentResource::getUrl('create')),
        ];
    }
}
