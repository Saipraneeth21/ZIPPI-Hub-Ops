<?php

namespace App\Filament\Resources\IncidentReports\Pages;

use App\Filament\Resources\IncidentReports\IncidentReportResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIncidentReport extends CreateRecord
{
    protected static string $resource = IncidentReportResource::class;

    /** Keep only "Create" and "Cancel" — drop "Create & create another". */
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCancelFormAction(),
        ];
    }
}
