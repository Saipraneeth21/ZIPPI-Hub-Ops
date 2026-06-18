<?php

namespace App\Filament\Resources\BikeCategories\Pages;

use App\Filament\Resources\BikeCategories\BikeCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBikeCategory extends CreateRecord
{
    protected static string $resource = BikeCategoryResource::class;
}
