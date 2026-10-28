<?php

declare(strict_types=1);

namespace App\Http\Requests\Trainer;

use App\Rules\Fields;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'brand_color' => ['nullable', Fields::HEX_COLOR],
            'logo' => ['nullable', ...Fields::LOGO],
            'remove_logo' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'brand_color.regex' => 'Use a hex color such as #00B300.',
            'logo.max' => 'The logo may not be larger than 2MB.',
            'logo.mimes' => 'The logo must be a PNG, JPG or SVG file.',
        ];
    }
}
