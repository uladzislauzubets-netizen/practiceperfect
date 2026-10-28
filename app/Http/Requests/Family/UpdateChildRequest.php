<?php

declare(strict_types=1);

namespace App\Http\Requests\Family;

use App\Enums\Gender;
use App\Rules\ChildAge;
use App\Rules\Fields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChildRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('profile'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'before:today', new ChildAge],
            'gender' => ['required', Rule::enum(Gender::class)],
            'school' => ['nullable', 'string', 'max:150'],
            'jersey_number' => ['nullable', 'string', 'max:10'],
            'allow_token_spending_without_approval' => ['boolean'],
            'photo' => ['nullable', ...Fields::IMAGE],
        ];
    }
}
