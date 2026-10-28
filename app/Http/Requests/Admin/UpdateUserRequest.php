<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Rules\Fields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var User $target */
        $target = $this->route('user');

        $rules = [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($target->id)],
            'phone' => ['nullable', 'string', Fields::PHONE],
            'school' => ['nullable', 'string', 'max:150'],
        ];

        if ($target->isTrainer()) {
            $rules += [
                'business_name' => ['required', 'string', 'max:150'],
                'address' => ['nullable', 'string', 'max:255'],
                'website' => ['nullable', 'url', 'max:2048'],
                'description' => ['nullable', 'string', 'max:5000'],
            ];
        }

        if ($target->isCoach()) {
            $rules += [
                'bio' => ['nullable', 'string', 'max:5000'],
                'credentials' => ['nullable', 'string', 'max:5000'],
                'certifications' => ['nullable', 'string', 'max:5000'],
                'is_public' => ['boolean'],
            ];
        }

        return $rules;
    }
}
