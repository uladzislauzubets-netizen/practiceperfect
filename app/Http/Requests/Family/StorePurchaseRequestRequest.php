<?php

declare(strict_types=1);

namespace App\Http\Requests\Family;

use App\Enums\PaymentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isChildAccount();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:1'],
            'payment_type' => ['required', Rule::enum(PaymentType::class)],
        ];
    }
}
