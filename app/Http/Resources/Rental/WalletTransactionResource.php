<?php
namespace App\Http\Resources\Rental;

use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'direction' => $this->direction,
            'amount' => (int) $this->amount,
            'balance_after' => (int) $this->balance_after,
            'source_type' => $this->source_type,
            'description' => $this->description,
            'created_at' => $this->created_at,
        ];
    }
}
