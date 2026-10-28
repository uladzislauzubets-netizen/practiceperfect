<?php

declare(strict_types=1);

namespace App\Http\Requests\Trainer;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlayerSkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('administerAsTrainer', $this->route('profile'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['skill_level' => ['nullable', 'string', 'max:50']];
    }
}
