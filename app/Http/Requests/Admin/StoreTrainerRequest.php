<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Actions\Users\CreateTrainerAccount;
use App\Models\User;
use App\Rules\Fields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrainerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'role' => ['required', Rule::in(['trainer'])],
            'business_name' => ['required', 'string', 'max:150'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', Fields::PHONE],
            'delivery' => ['required', Rule::in([CreateTrainerAccount::DELIVERY_INVITE_LINK, CreateTrainerAccount::DELIVERY_TEMPORARY_PASSWORD])],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['email.unique' => 'A user with this email already exists.'];
    }
}
