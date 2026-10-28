<?php

declare(strict_types=1);

namespace App\Http\Requests\Join;

use App\Enums\Gender;
use App\Rules\ChildAge;
use App\Rules\Fields;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterPlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() === null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
            'trains_self' => $this->boolean('trains_self'),
            'children' => array_values((array) $this->input('children', [])),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
            'phone' => ['required', 'string', Fields::PHONE],
            'trains_self' => ['boolean'],
            'confirm_adult' => ['required_if:trains_self,true', 'accepted_if:trains_self,true'],
            'gender' => ['nullable', 'required_if:trains_self,true', Rule::enum(Gender::class)],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'children' => ['array', 'max:10'],
            'children.*.first_name' => ['required', 'string', 'max:100'],
            'children.*.last_name' => ['required', 'string', 'max:100'],
            'children.*.birth_date' => ['required', 'date', 'before:today', new ChildAge],
            'children.*.gender' => ['required', Rule::enum(Gender::class)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'email.unique' => 'An account with this email already exists. Log in to connect with this trainer.',
            'confirm_adult.accepted_if' => 'You must confirm you are 18 or older to register yourself.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $this->boolean('trains_self') && $this->input('children') === []) {
                    $validator->errors()->add('trains_self', 'Choose who will be training: yourself, a child, or both.');
                }
            },
        ];
    }

    /** @return array<string, mixed> */
    public function registrationData(): array
    {
        return [
            ...$this->safe()->only(['first_name', 'last_name', 'email', 'password', 'phone', 'gender', 'birth_date']),
            'trains_self' => $this->boolean('trains_self'),
            'children' => $this->validated('children', []),
        ];
    }
}
