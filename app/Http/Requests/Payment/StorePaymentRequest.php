<?php

declare(strict_types=1);

namespace App\Http\Requests\Payment;

use App\Http\Requests\ApiFormRequest;

class StorePaymentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01|max:9999999.99',
            'description' => 'required|string|max:255',
        ];
    }
}
