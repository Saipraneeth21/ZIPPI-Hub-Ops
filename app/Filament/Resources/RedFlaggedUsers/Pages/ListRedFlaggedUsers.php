<?php

namespace App\Filament\Resources\RedFlaggedUsers\Pages;

use App\Filament\Resources\RedFlaggedUsers\RedFlaggedUserResource;
use Filament\Resources\Pages\ListRecords;

class ListRedFlaggedUsers extends ListRecords
{
    protected static string $resource = RedFlaggedUserResource::class;
}
