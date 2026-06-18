<?php
namespace App\Http\Resources\Rental;

use Illuminate\Http\Resources\Json\JsonResource;

class BikeResource extends JsonResource
{
    public function toArray($request): array
    {
        $p = $this->pricing;
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->whenLoaded('category', fn () => $this->category->name),
            'registration_no' => $this->registration_no,
            'fuel_type' => $this->fuel_type,
            'year' => $this->year,
            'status' => $this->status,
            'specifications' => $this->specifications,
            'security_deposit' => (int) $this->security_deposit_amount,
            'pricing' => $p ? [
                'hourly' => (int) $p->hourly_rate,
                'daily' => (int) $p->daily_rate,
                'monthly' => (int) $p->monthly_rate,
                'min_hours' => (int) $p->min_hours,
            ] : null,
            'images' => $this->whenLoaded('images', fn () => $this->images->pluck('image_path')),
            'primary_image_url' => $this->whenLoaded('primaryImage', fn () => $this->primaryImage?->image_path),
            'hub' => $this->whenLoaded('hub', fn () => [
                'id' => $this->hub->id, 'name' => $this->hub->name, 'address' => $this->hub->address,
                'lat' => $this->hub->latitude, 'lng' => $this->hub->longitude,
            ]),
        ];
    }
}
