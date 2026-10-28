<?php

declare(strict_types=1);

namespace App\Http\Requests\Join;

use Illuminate\Foundation\Http\FormRequest;

class AssociateProfilesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPlayer() === true && ! $this->user()->isChildAccount();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'profile_ids' => ['required', 'array', 'min:1'],
            'profile_ids.*' => ['integer'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['profile_ids.required' => 'Select at least one person.', 'profile_ids.min' => 'Select at least one person.'];
    }
}
