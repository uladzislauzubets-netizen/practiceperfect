<?php

declare(strict_types=1);

namespace App\Http\Requests\Profile;

use App\Rules\Fields;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $user = $this->user();
        $photo = ['photo' => ['nullable', ...Fields::IMAGE], 'remove_photo' => ['boolean']];

        if ($user->isChildAccount()) {
            return [
                ...$photo,
                'jersey_number' => ['nullable', 'string', 'max:10'],
                'school' => ['nullable', 'string', 'max:150'],
            ];
        }

        $rules = [
            ...$photo,
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', Fields::PHONE],
            'photo_url' => ['nullable', 'url:https', 'max:2048'],
        ];

        if ($user->isTrainer()) {
            $rules += [
                'school' => ['nullable', 'string', 'max:150'],
                'business_name' => ['required', 'string', 'max:150'],
                'address' => ['nullable', 'string', 'max:255'],
                'website' => ['nullable', 'url', 'max:2048'],
                'description' => ['nullable', 'string', 'max:5000'],
            ];
        }

        if ($user->isCoach()) {
            $rules += [
                'school' => ['nullable', 'string', 'max:150'],
                'bio' => ['nullable', 'string', 'max:5000'],
                'credentials' => ['nullable', 'string', 'max:5000'],
                'certifications' => ['nullable', 'string', 'max:5000'],
                'is_public' => ['boolean'],
            ];
        }

        if ($user->isSuperAdmin()) {
            $rules += ['school' => ['nullable', 'string', 'max:150']];
        }

        if ($user->isPlayer()) {
            $rules += [
                'school' => ['nullable', 'string', 'max:150'],
                'jersey_number' => ['nullable', 'string', 'max:10'],
                'emergency_contact_name' => ['nullable', 'string', 'max:150'],
                'emergency_contact_phone' => ['nullable', 'string', Fields::PHONE],
                'emergency_contact_relationship' => ['nullable', 'string', 'max:100'],
            ];
        }

        return $rules;
    }
}
