<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client' => new ClientResource($this->whenLoaded('client')),
            'gateway' => new GatewayResource($this->whenLoaded('gateway')),
            'amount' => $this->amount,
            'status' => $this->status,
            'external_id' => $this->external_id,
            'card_last_numbers' => $this->card_last_numbers,
            'products' => TransactionProductResource::collection($this->whenLoaded('transactionProducts')),
            'created_at' => $this->created_at,
        ];
    }
}
