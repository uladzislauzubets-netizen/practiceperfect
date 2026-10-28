<?php

declare(strict_types=1);

namespace App\Enums;

enum ShareLinkState: string
{
    case Valid = 'valid';
    case Inactive = 'inactive';
    case Expired = 'expired';
    case Exhausted = 'exhausted';
    case TrainerUnavailable = 'trainer_unavailable';

    public function message(): string
    {
        return match ($this) {
            self::Valid => 'This link is valid.',
            self::Inactive => 'This link is no longer active. Ask your trainer for a new one.',
            self::Expired => 'This link has expired. Ask your trainer to resend the invitation.',
            self::Exhausted => 'This link has already been used. Ask your trainer to resend the invitation.',
            self::TrainerUnavailable => 'This trainer is not accepting registrations right now.',
        };
    }
}
