<?php

declare(strict_types=1);

namespace App\Http\Requests\Trainer;

use Illuminate\Foundation\Http\FormRequest;

class OverrideCoachAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'coach_id' => ['required', 'integer'],
            'event_id' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['reason.required' => 'A reason is required to override a coach\'s availability.'];
    }
}
