<?php

declare(strict_types=1);

namespace App\Http\Requests\Trainer;

use Illuminate\Foundation\Http\FormRequest;

class InviteCoachRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:100'],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
