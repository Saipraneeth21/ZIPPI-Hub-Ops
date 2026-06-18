<?php

namespace App\Filament\Resources\Kyc\Pages;

use App\Filament\Resources\Kyc\KycResource;
use Filament\Resources\Pages\ListRecords;

class ListKycReviews extends ListRecords
{
    protected static string $resource = KycResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
