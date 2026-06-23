<?php

namespace App\Filament\Resources\Bikes\Pages;

use App\Filament\Resources\Bikes\BikeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBike extends CreateRecord
{
    protected static string $resource = BikeResource::class;

    // Only "Create" and "Cancel" — no "Create & create another".
    protected static bool $canCreateAnother = false;

    /** Holds the uploaded photo path between create and afterCreate. */
    protected ?string $primaryImagePath = null;

    /**
     * `primary_image` lives on rental_bike_images, not rental_bikes — pull it
     * out of the bike payload before the record is created.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->primaryImagePath = $data['primary_image'] ?? null;
        unset($data['primary_image']);

        return $data;
    }

    /** Persist the uploaded photo as the bike's primary image. */
    protected function afterCreate(): void
    {
        if ($this->primaryImagePath !== null) {
            $this->record->images()->create([
                'image_path' => $this->primaryImagePath,
                'is_primary' => true,
            ]);
        }
    }
}
