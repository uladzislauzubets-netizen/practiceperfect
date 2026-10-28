<?php

declare(strict_types=1);

namespace App\Http\Requests\Family;

use Illuminate\Foundation\Http\FormRequest;

class AddChildTrainerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('profile'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'share_code' => ['nullable', 'string', 'max:255', 'required_without:trainer_id'],
            'trainer_id' => ['nullable', 'integer', 'required_without:share_code'],
        ];
    }
}
