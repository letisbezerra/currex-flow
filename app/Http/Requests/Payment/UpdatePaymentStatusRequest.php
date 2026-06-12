<?php

declare(strict_types=1);

namespace App\Http\Requests\Payment;

use App\Enums\PaymentStatus;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentStatusRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                PaymentStatus::Approved->value,
                PaymentStatus::Rejected->value,
            ])],
        ];
    }
}
