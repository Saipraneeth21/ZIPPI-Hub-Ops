<?php
namespace App\Http\Resources\Rental;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'booking_code' => $this->booking_code,
            'status' => $this->status,
            'duration_type' => $this->duration_type,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'actual_start_at' => $this->actual_start_at,
            'actual_end_at' => $this->actual_end_at,
            'computed_units' => $this->computed_units,
            'amounts' => [
                'base' => (int) $this->base_amount,
                'tax' => (int) $this->tax_amount,
                'platform_fee' => (int) $this->platform_fee,
                'discount' => (int) $this->discount_amount,
                'deposit' => (int) $this->deposit_amount,
                'total' => (int) $this->total_amount,
                'late_penalty' => (int) $this->late_penalty,
            ],
            'bike' => $this->whenLoaded('bike', fn () => [
                'id' => $this->bike->id, 'name' => $this->bike->name,
            ]),
            'hold_expires_at' => $this->hold_expires_at,
            'created_at' => $this->created_at,
        ];
    }
}
