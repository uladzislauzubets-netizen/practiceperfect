<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentType: string
{
    case Usd = 'usd';
    case Tokens = 'tokens';
}
