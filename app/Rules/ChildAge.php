<?php

declare(strict_types=1);

namespace App\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Throwable;

class ChildAge implements ValidationRule
{
    public const MIN = 1;

    public const MAX = 18;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $age = Carbon::parse((string) $value)->age;
        } catch (Throwable) {
            $fail('The :attribute is not a valid date.');

            return;
        }

        if ($age < self::MIN || $age > self::MAX) {
            $fail('The child must be between '.self::MIN.' and '.self::MAX.' years old.');
        }
    }
}
