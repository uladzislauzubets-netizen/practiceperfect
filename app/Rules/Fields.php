<?php

declare(strict_types=1);

namespace App\Rules;

final class Fields
{
    public const PHONE = 'regex:/^\+?[0-9\s().-]{7,20}$/';

    public const HEX_COLOR = 'regex:/^#[0-9A-Fa-f]{6}$/';

    public const IMAGE = ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'];

    public const LOGO = ['file', 'mimes:png,jpg,jpeg,svg', 'max:2048'];
}
