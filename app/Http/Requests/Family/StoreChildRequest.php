<?php

declare(strict_types=1);

namespace App\Http\Requests\Family;

use App\Enums\Gender;
use App\Models\PlayerProfile;
use App\Rules\ChildAge;
use App\Rules\Fields;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChildRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isPlayer() && ! $this->user()->isChildAccount();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'trainer_ids' => array_map('intval', (array) $this->input('trainer_ids', [])),
            'confirm_duplicate' => $this->boolean('confirm_duplicate'),
        ]);
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
            'photo' => ['nullable', ...Fields::IMAGE],
            'trainer_ids' => ['array'],
            'trainer_ids.*' => ['integer', Rule::in($this->user()->trainerIds())],
            'confirm_duplicate' => ['boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->boolean('confirm_duplicate') || $validator->errors()->isNotEmpty()) {
                    return;
                }

                $duplicate = PlayerProfile::query()
                    ->where('parent_id', $this->user()->id)
                    ->whereRaw('lower(first_name) = ?', [strtolower((string) $this->input('first_name'))])
                    ->whereRaw('lower(last_name) = ?', [strtolower((string) $this->input('last_name'))])
                    ->whereYear('birth_date', date('Y', strtotime((string) $this->input('birth_date'))))
                    ->exists();

                if ($duplicate) {
                    $validator->errors()->add('duplicate', 'A child with a similar name and age already exists. Add anyway?');
                }
            },
        ];
    }
}
