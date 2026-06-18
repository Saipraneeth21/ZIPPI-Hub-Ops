<?php

namespace App\Filament\Resources\Kyc\Pages;

use App\Filament\Resources\Kyc\KycResource;
use App\Filament\Resources\Kyc\Support\KycReviewActions;
use Filament\Resources\Pages\ViewRecord;

class ViewKycReview extends ViewRecord
{
    protected static string $resource = KycResource::class;

    protected function getHeaderActions(): array
    {
        return [
            KycReviewActions::approve(),
            KycReviewActions::reject(),
        ];
    }
}
