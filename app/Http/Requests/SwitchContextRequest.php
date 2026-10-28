<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SwitchContextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isPlayer();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'profile_id' => ['required', 'integer'],
            'trainer_id' => ['required', 'integer'],
        ];
    }
}
