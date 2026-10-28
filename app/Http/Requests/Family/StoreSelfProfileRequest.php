<?php

declare(strict_types=1);

namespace App\Http\Requests\Family;

use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSelfProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isPlayer() && ! $this->user()->isChildAccount();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'confirm_adult' => ['accepted'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'birth_date' => ['nullable', 'date', 'before:today'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['confirm_adult.accepted' => 'You must confirm you are 18 or older to train yourself.'];
    }
}
