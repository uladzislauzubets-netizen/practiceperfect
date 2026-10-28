<?php

declare(strict_types=1);

namespace App\Enums;

enum ShareLinkType: string
{
    case Player = 'player';
    case Coach = 'coach';
}
