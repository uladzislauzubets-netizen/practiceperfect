<?php

declare(strict_types=1);

namespace App\Http\Requests\Trainer;

use Illuminate\Foundation\Http\FormRequest;

class CheckCoachAvailabilityRequest extends FormRequest
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
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ];
    }
}
