<?php

declare(strict_types=1);

namespace App\Http\Requests\Availability;

use Illuminate\Foundation\Http\FormRequest;

class SaveAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $slots = collect((array) $this->input('slots', []))->map(fn ($slot) => [
            ...(array) $slot,
            'is_available' => filter_var($slot['is_available'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ])->all();

        $this->merge(['slots' => $slots]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'slots' => ['present', 'array', 'max:100'],
            'slots.*.day_of_week' => ['required', 'integer', 'between:1,7'],
            'slots.*.is_available' => ['boolean'],
            'slots.*.start_time' => ['required_if:slots.*.is_available,true', 'nullable', 'date_format:H:i'],
            'slots.*.end_time' => ['required_if:slots.*.is_available,true', 'nullable', 'date_format:H:i'],
        ];
    }

    /** @return array<int, array{day_of_week: int, start_time: string, end_time: string, is_available: bool}> */
    public function slots(): array
    {
        return collect($this->validated('slots'))->map(fn (array $slot) => [
            'day_of_week' => (int) $slot['day_of_week'],
            'start_time' => (string) ($slot['start_time'] ?? '00:00'),
            'end_time' => (string) ($slot['end_time'] ?? '23:59'),
            'is_available' => (bool) $slot['is_available'],
        ])->all();
    }
}
