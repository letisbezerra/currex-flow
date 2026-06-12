<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\PaymentRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentRequestResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var PaymentRequest $payment */
        $payment = $this->resource;

        return [
            'id' => $payment->id,
            'amount' => $payment->amount,
            'currency_code' => $payment->currency_code,
            'amount_in_eur' => $payment->amount_in_eur,
            'description' => $payment->description,
            'status' => $payment->status->value,
            'exchange_rate' => $payment->exchange_rate,
            'exchange_rate_source' => $payment->exchange_rate_source,
            'exchange_rate_fetched_at' => $payment->exchange_rate_fetched_at,
            'expires_at' => $payment->expires_at,
            'reviewed_by' => $payment->reviewed_by,
            'reviewed_at' => $payment->reviewed_at,
            'created_at' => $payment->created_at,
            'user' => UserResource::make($this->whenLoaded('user')),
        ];
    }
}
